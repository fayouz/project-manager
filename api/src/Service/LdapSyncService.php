<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\LdapConfiguration;
use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Entity\User;
use App\Repository\LdapConfigurationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;

class LdapSyncService
{
    public function __construct(
        private readonly LdapConfigurationRepository $ldapConfigurationRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getActiveConfiguration(): ?LdapConfiguration
    {
        $config = $this->ldapConfigurationRepository->findOneBy(['enabled' => true]);
        if (!$config) {
            $config = $this->ldapConfigurationRepository->findOneBy([]);
        }

        return $config;
    }

    /**
     * @return array{
     *     success: bool,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     total: int,
     *     message?: string
     * }
     */
    public function syncUsers(?LdapConfiguration $config = null): array
    {
        $config ??= $this->getActiveConfiguration();
        if (!$config || !$config->getHost() || !$config->getBaseDn()) {
            return [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
                'message' => 'Aucune configuration LDAP valide trouvée.',
            ];
        }

        try {
            $ldap = $this->createLdapClient($config);
            if ($config->getBindDn() && $config->getBindPassword()) {
                $ldap->bind($config->getBindDn(), $config->getBindPassword());
            } else {
                $ldap->bind();
            }

            $imageAttr = $config->getImageAttribute() ?: 'jpegPhoto';
            $emailAttr = $config->getEmailAttribute() ?: 'mail';
            $usernameAttr = $config->getUsernameAttribute() ?: 'sAMAccountName';

            $attributesToFetch = array_values(array_unique(array_filter([
                'givenName',
                'sn',
                'mail',
                'sAMAccountName',
                'uid',
                'displayName',
                'title',
                'department',
                'manager',
                'jpegPhoto',
                'thumbnailPhoto',
                'userPrincipalName',
                'cn',
                'distinguishedName',
                $imageAttr,
                $emailAttr,
                $usernameAttr,
            ])));

            $query = $ldap->query(
                $config->getBaseDn(),
                '(&(objectClass=person)(|(mail=*)(sAMAccountName=*)(uid=*)))'
            );
            $results = $query->execute();

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($results as $entry) {
                $extracted = $this->extractUserDataFromEntry($entry, $imageAttr, $emailAttr);
                $mail = $extracted['email'];
                $uid = $extracted['username'];
                $dn = $entry->getDn();
                $extracted['dn'] = $dn;

                // Vérifier si l'entrée correspond aux filtres de recherche regex
                if (!$this->matchesSearchFilters($config, $extracted, $entry)) {
                    $this->logger->info('LDAP sync: utilisateur ignoré car il ne correspond pas aux filtres regex: {username} ({email})', [
                        'username' => $uid,
                        'email' => $mail,
                    ]);
                    $skipped++;
                    continue;
                }

                $email = $mail ?: ($uid ? $uid . '@ldap.local' : null);
                if (!$email) {
                    $skipped++;
                    continue;
                }

                $existing = $this->userRepository->findOneBy(['email' => $email]);
                if ($existing instanceof LocalUser) {
                    $this->logger->info('LDAP sync: utilisateur local préservé pour email {email}', ['email' => $email]);
                    $skipped++;
                    continue;
                }

                if ($existing instanceof LdapUser) {
                    $existing->setUsername($uid ?: $email);
                    $existing->setDistinguishedName($dn);
                    $existing->setLdapUid($uid);
                    $existing->setSyncedAt(new \DateTimeImmutable());
                    if ($extracted['firstname'] !== null) $existing->setFirstName($extracted['firstname']);
                    if ($extracted['lastname'] !== null) $existing->setLastName($extracted['lastname']);
                    if ($extracted['displayName'] !== null) $existing->setDisplayName($extracted['displayName']);
                    if ($extracted['title'] !== null) $existing->setTitle($extracted['title']);
                    if ($extracted['department'] !== null) $existing->setDepartment($extracted['department']);
                    if ($extracted['managerDn'] !== null) $existing->setManagerDn($extracted['managerDn']);
                    if ($extracted['image'] !== null) {
                        $existing->setImage($extracted['image']);
                    }
                    if ($extracted['managerDn'] !== null) {
                        $manager = $this->findOrCreateManagerFromDn($extracted['managerDn'], $config, $ldap);
                        if ($manager && $manager !== $existing && ($existing->getId() === null || $manager->getId() !== $existing->getId())) {
                            $existing->setManager($manager);
                        }
                    }
                    $updated++;
                } else {
                    $newUser = new LdapUser();
                    $newUser->setEmail($email);
                    $newUser->setUsername($uid ?: $email);
                    $newUser->setDistinguishedName($dn);
                    $newUser->setLdapUid($uid);
                    $newUser->setRoles(['ROLE_USER']);
                    $newUser->setSyncedAt(new \DateTimeImmutable());
                    if ($extracted['firstname'] !== null) $newUser->setFirstName($extracted['firstname']);
                    if ($extracted['lastname'] !== null) $newUser->setLastName($extracted['lastname']);
                    if ($extracted['displayName'] !== null) $newUser->setDisplayName($extracted['displayName']);
                    if ($extracted['title'] !== null) $newUser->setTitle($extracted['title']);
                    if ($extracted['department'] !== null) $newUser->setDepartment($extracted['department']);
                    if ($extracted['managerDn'] !== null) $newUser->setManagerDn($extracted['managerDn']);
                    if ($extracted['image'] !== null) {
                        $newUser->setImage($extracted['image']);
                    }
                    if ($extracted['managerDn'] !== null) {
                        $manager = $this->findOrCreateManagerFromDn($extracted['managerDn'], $config, $ldap);
                        if ($manager && $manager !== $newUser && ($newUser->getId() === null || $manager->getId() !== $newUser->getId())) {
                            $newUser->setManager($manager);
                        }
                    }

                    $this->entityManager->persist($newUser);
                    $created++;
                }
            }

            $this->entityManager->flush();

            return [
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => $created + $updated,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la synchronisation LDAP: ' . $e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recherche ou crée un utilisateur pour le manager à partir de son Distinguished Name (DN).
     *
     * @param list<string> $visitedDns
     */
    public function findOrCreateManagerFromDn(
        string $managerDn,
        ?LdapConfiguration $config = null,
        ?LdapInterface $ldap = null,
        array &$visitedDns = []
    ): ?User {
        $managerDn = trim($managerDn);
        if ($managerDn === '') {
            return null;
        }

        $lowerDn = strtolower($managerDn);
        if (in_array($lowerDn, $visitedDns, true)) {
            return null; // Éviter toute référence circulaire
        }
        $visitedDns[] = $lowerDn;

        // 1. Recherche par DN déjà existant dans notre base
        $existingManager = null;
        try {
            $ldapUserRepo = $this->entityManager->getRepository(LdapUser::class);
            if ($ldapUserRepo !== null) {
                $existingManager = $ldapUserRepo->findOneBy(['distinguishedName' => $managerDn]);
            }
        } catch (\Throwable) {
        }
        if ($existingManager instanceof User) {
            return $existingManager;
        }

        // Extraction du CN depuis le DN pour référence / fallback
        $cn = null;
        if (preg_match('/(?:^|,\s*)cn=([^,]+)/i', $managerDn, $matches)) {
            $cn = trim($matches[1]);
        }

        $config ??= $this->getActiveConfiguration();

        $entry = null;
        if ($config && $config->getHost()) {
            if ($ldap === null) {
                try {
                    $ldap = $this->createLdapClient($config);
                    if ($config->getBindDn() && $config->getBindPassword()) {
                        $ldap->bind($config->getBindDn(), $config->getBindPassword());
                    } else {
                        $ldap->bind();
                    }
                } catch (\Throwable $bindException) {
                    $this->logger->warning('Impossible de lier LDAP pour la recherche du manager: ' . $bindException->getMessage());
                }
            }

            if ($ldap !== null) {
                try {
                    // Lecture directe en SCOPE_BASE sur le DN du manager
                    $query = $ldap->query($managerDn, '(objectClass=*)', ['scope' => QueryInterface::SCOPE_BASE]);
                    $results = iterator_to_array($query->execute());
                    if (!empty($results)) {
                        $entry = $results[0];
                    }
                } catch (\Throwable $queryException) {
                    $this->logger->warning('Recherche directe du manager par DN échouée: ' . $queryException->getMessage(), [
                        'managerDn' => $managerDn,
                    ]);
                }
            }
        }

        if ($entry instanceof Entry) {
            $imageAttr = $config?->getImageAttribute() ?: 'jpegPhoto';
            $emailAttr = $config?->getEmailAttribute() ?: 'mail';
            $extracted = $this->extractUserDataFromEntry($entry, $imageAttr, $emailAttr);

            $mail = $extracted['email'];
            $uid = $extracted['username'] ?: ($cn ?: null);

            // Vérifier si cet utilisateur existe déjà par email ou username
            $userByEmail = $mail ? $this->userRepository->findOneBy(['email' => $mail]) : null;
            if ($userByEmail instanceof User) {
                if ($userByEmail instanceof LdapUser && !$userByEmail->getDistinguishedName()) {
                    $userByEmail->setDistinguishedName($entry->getDn() ?: $managerDn);
                }
                if ($extracted['firstname'] !== null && !$userByEmail->getFirstName()) $userByEmail->setFirstName($extracted['firstname']);
                if ($extracted['lastname'] !== null && !$userByEmail->getLastName()) $userByEmail->setLastName($extracted['lastname']);
                if ($extracted['displayName'] !== null && !$userByEmail->getDisplayName()) $userByEmail->setDisplayName($extracted['displayName']);
                if ($extracted['title'] !== null && !$userByEmail->getTitle()) $userByEmail->setTitle($extracted['title']);
                if ($extracted['department'] !== null && !$userByEmail->getDepartment()) $userByEmail->setDepartment($extracted['department']);
                if ($extracted['image'] !== null && !$userByEmail->getImage()) $userByEmail->setImage($extracted['image']);
                $this->entityManager->flush();

                return $userByEmail;
            }

            $userByUsername = $uid ? $this->userRepository->findOneBy(['username' => $uid]) : null;
            if ($userByUsername instanceof User) {
                if ($userByUsername instanceof LdapUser && !$userByUsername->getDistinguishedName()) {
                    $userByUsername->setDistinguishedName($entry->getDn() ?: $managerDn);
                }
                if ($mail && !$userByUsername->getEmail()) $userByUsername->setEmail($mail);
                if ($extracted['firstname'] !== null && !$userByUsername->getFirstName()) $userByUsername->setFirstName($extracted['firstname']);
                if ($extracted['lastname'] !== null && !$userByUsername->getLastName()) $userByUsername->setLastName($extracted['lastname']);
                if ($extracted['displayName'] !== null && !$userByUsername->getDisplayName()) $userByUsername->setDisplayName($extracted['displayName']);
                if ($extracted['title'] !== null && !$userByUsername->getTitle()) $userByUsername->setTitle($extracted['title']);
                if ($extracted['department'] !== null && !$userByUsername->getDepartment()) $userByUsername->setDepartment($extracted['department']);
                if ($extracted['image'] !== null && !$userByUsername->getImage()) $userByUsername->setImage($extracted['image']);
                $this->entityManager->flush();

                return $userByUsername;
            }

            // Création d'un nouvel utilisateur LdapUser pour le manager
            $newManager = new LdapUser();
            $email = $mail ?: ($uid ? $uid . '@ldap.local' : 'manager-' . substr(md5($managerDn), 0, 8) . '@ldap.local');
            $newManager->setEmail($email);
            $newManager->setUsername($uid ?: $email);
            $newManager->setDistinguishedName($entry->getDn() ?: $managerDn);
            $newManager->setLdapUid($uid ?: $email);
            $newManager->setRoles(['ROLE_USER']);
            $newManager->setSyncedAt(new \DateTimeImmutable());
            if ($extracted['firstname'] !== null) $newManager->setFirstName($extracted['firstname']);
            if ($extracted['lastname'] !== null) $newManager->setLastName($extracted['lastname']);
            if ($extracted['displayName'] !== null) $newManager->setDisplayName($extracted['displayName']);
            if ($extracted['title'] !== null) $newManager->setTitle($extracted['title']);
            if ($extracted['department'] !== null) $newManager->setDepartment($extracted['department']);
            if ($extracted['managerDn'] !== null) $newManager->setManagerDn($extracted['managerDn']);
            if ($extracted['image'] !== null) $newManager->setImage($extracted['image']);

            $this->entityManager->persist($newManager);
            $this->entityManager->flush();

            $this->logger->info('Manager LDAP créé avec succès depuis son managerDn: {username} ({email})', [
                'username' => $newManager->getUsername(),
                'email' => $newManager->getEmail(),
                'managerDn' => $managerDn,
            ]);

            return $newManager;
        }

        // Fallback sans entrée LDAP :
        if ($cn !== null && $cn !== '') {
            $userByCn = $this->userRepository->findOneBy(['displayName' => $cn])
                ?? $this->userRepository->findOneBy(['username' => $cn]);
            if ($userByCn instanceof User) {
                if ($userByCn instanceof LdapUser && !$userByCn->getDistinguishedName()) {
                    $userByCn->setDistinguishedName($managerDn);
                    $this->entityManager->flush();
                }

                return $userByCn;
            }
        }

        // Création minimale d'un LdapUser pour le manager
        $newManager = new LdapUser();
        $slug = $cn ? preg_replace('/[^a-zA-Z0-9]/', '', strtolower($cn)) : '';
        $email = ($slug !== '' ? $slug : 'manager-' . substr(md5($managerDn), 0, 8)) . '@ldap.local';

        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            $email = 'manager-' . substr(md5($managerDn), 0, 8) . '@ldap.local';
        }

        $newManager->setEmail($email);
        $newManager->setUsername($cn ?: ('manager-' . substr(md5($managerDn), 0, 8)));
        $newManager->setDisplayName($cn ?: 'Manager');
        $newManager->setDistinguishedName($managerDn);
        $newManager->setRoles(['ROLE_USER']);
        $newManager->setSyncedAt(new \DateTimeImmutable());

        $this->entityManager->persist($newManager);
        $this->entityManager->flush();

        $this->logger->info('Manager LDAP créé depuis le managerDn: {managerDn}', [
            'managerDn' => $managerDn,
        ]);

        return $newManager;
    }

    /**
     * Extrait les informations d'un utilisateur depuis une entrée LDAP.
     *
     * @return array{
     *     firstname: ?string,
     *     lastname: ?string,
     *     email: ?string,
     *     username: ?string,
     *     displayName: ?string,
     *     title: ?string,
     *     department: ?string,
     *     managerDn: ?string,
     *     image: ?string
     * }
     */
    private function extractUserDataFromEntry(Entry $entry, ?string $imageAttr = null, ?string $emailAttr = null): array
    {
        $firstname = $entry->getAttribute('givenName')[0] ?? null;
        $lastname = $entry->getAttribute('sn')[0] ?? null;
        $email = ($emailAttr ? ($entry->getAttribute($emailAttr)[0] ?? null) : null)
            ?? $entry->getAttribute('mail')[0]
            ?? $entry->getAttribute('userPrincipalName')[0]
            ?? null;
        $username = $entry->getAttribute('sAMAccountName')[0]
            ?? $entry->getAttribute('uid')[0]
            ?? $entry->getAttribute('cn')[0]
            ?? null;
        $displayName = $entry->getAttribute('displayName')[0] ?? null;
        $title = $entry->getAttribute('title')[0] ?? null;
        $department = $entry->getAttribute('department')[0] ?? null;
        $managerDn = $entry->getAttribute('manager')[0] ?? null;

        // Avatar depuis LDAP (jpegPhoto ou thumbnailPhoto ou attribut personnalisé)
        $avatar = $this->extractImageFromEntry($entry, $imageAttr);

        // Log des données extraites pour débogage
        $this->logger->debug('Données extraites de LDAP:', [
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'title' => $title,
            'department' => $department,
            'managerDn' => $managerDn,
        ]);

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'username' => $username,
            'displayName' => $displayName,
            'title' => $title,
            'department' => $department,
            'managerDn' => $managerDn,
            'image' => $avatar,
        ];
    }

    /**
     * Détermine l'attribut CN d'un utilisateur pour la recherche LDAP.
     */
    private function resolveCnForUser(User $user): ?string
    {
        if ($user instanceof LdapUser && $user->getDistinguishedName()) {
            if (preg_match('/(?:^|,\s*)cn=([^,]+)/i', $user->getDistinguishedName(), $matches)) {
                return trim($matches[1]);
            }
        }

        if ($user instanceof LdapUser && $user->getLdapUid()) {
            return $user->getLdapUid();
        }

        if ($user->getUsername()) {
            return $user->getUsername();
        }

        if ($user->getEmail()) {
            return explode('@', $user->getEmail())[0];
        }

        return null;
    }

    /**
     * Rafraîchit les informations d'un utilisateur LDAP existant depuis le serveur LDAP.
     * Met à jour l'image et l'email, mais préserve le nom d'utilisateur (sAMAccountName / username) existant.
     * Recherche strictement par l'attribut cn.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     user?: LdapUser
     * }
     */
    public function refreshUser(User $user, ?LdapConfiguration $config = null): array
    {
        if (!$user instanceof LdapUser) {
            return [
                'success' => false,
                'message' => 'Seuls les comptes synchronisés via LDAP peuvent être rafraîchis.',
            ];
        }

        $config ??= $this->getActiveConfiguration();
        if (!$config || !$config->getHost() || !$config->getBaseDn()) {
            return [
                'success' => false,
                'message' => 'Aucune configuration LDAP valide trouvée.',
            ];
        }

        try {
            $ldap = $this->createLdapClient($config);
            if ($config->getBindDn() && $config->getBindPassword()) {
                $ldap->bind($config->getBindDn(), $config->getBindPassword());
            } else {
                $ldap->bind();
            }

            $imageAttr = $config->getImageAttribute() ?: 'jpegPhoto';
            $emailAttr = $config->getEmailAttribute() ?: 'mail';
            $usernameAttr = $config->getUsernameAttribute() ?: 'sAMAccountName';

            $username = $user->getUsername();
            $ldapUid = $user->getLdapUid();
            $email = $user->getEmail();
            $cn = $this->resolveCnForUser($user);

            $targetValue = $cn ?: $ldapUid ?: $username ?: ($email ? explode('@', $email)[0] : null);

            // 1. Application de la regex de requête (queryRegex) si configurée
            $queryRegex = $config->getQueryRegex() ? trim($config->getQueryRegex()) : null;
            if ($queryRegex !== null && $queryRegex !== '') {
                $valueToMatch = $username ?? $ldapUid ?? $cn ?? $email;
                if ($valueToMatch !== null) {
                    $delimited = preg_match('/^([\/#~%]).*\1[imsxADSUXJu]*$/s', $queryRegex)
                        ? $queryRegex
                        : ('/' . str_replace('/', '\/', $queryRegex) . '/i');

                    if (@preg_match($delimited, $valueToMatch, $matches)) {
                        if (isset($matches[1]) && $matches[1] !== '') {
                            $targetValue = $matches[1];
                        }
                    } else {
                        return [
                            'success' => false,
                            'message' => sprintf('L\'utilisateur "%s" ne correspond pas à la regex de requête (%s) configurée dans les paramètres LDAP.', $valueToMatch, $queryRegex),
                        ];
                    }
                }
            }

            if (!$targetValue) {
                return [
                    'success' => false,
                    'message' => 'Impossible de déterminer l\'identifiant pour la recherche LDAP de cet utilisateur.',
                ];
            }

            $escapedTarget = $ldap->escape($targetValue, '', LdapInterface::ESCAPE_FILTER);
            $escapedUsername = $ldap->escape($username ?? $targetValue, '', LdapInterface::ESCAPE_FILTER);
            $escapedCn = $ldap->escape($cn ?? $targetValue, '', LdapInterface::ESCAPE_FILTER);
            $escapedUid = $ldap->escape($ldapUid ?? $targetValue, '', LdapInterface::ESCAPE_FILTER);
            $escapedEmail = $ldap->escape($email ?? '', '', LdapInterface::ESCAPE_FILTER);

            // 2. Construction de la query / filtre LDAP
            $configuredSearchFilter = $config->getSearchFilter() ? trim($config->getSearchFilter()) : null;
            if ($configuredSearchFilter !== null && $configuredSearchFilter !== '') {
                $filter = str_replace(
                    ['{username}', '{uid}', '{cn}', '{email}', '{identifier}', '{query}'],
                    [$escapedUsername, $escapedUid, $escapedCn, $escapedEmail, $escapedTarget, $escapedTarget],
                    $configuredSearchFilter
                );
                if ($filter === $configuredSearchFilter) {
                    if (str_contains($configuredSearchFilter, '%s')) {
                        $filter = sprintf($configuredSearchFilter, $escapedTarget);
                    } elseif (!str_starts_with($configuredSearchFilter, '(')) {
                        $filter = sprintf('(%s=%s)', $configuredSearchFilter, $escapedTarget);
                    }
                }
            } else {
                $filter = sprintf('(cn=%s)', $escapedCn);
            }

            $entry = null;

            try {
                $query = $ldap->query($config->getBaseDn(), $filter);
                $results = iterator_to_array($query->execute());
                $entry = !empty($results) ? $results[0] : null;
            } catch (\Throwable $searchException) {
                $this->logger->warning('Recherche LDAP sous baseDn échouée: ' . $searchException->getMessage(), [
                    'baseDn' => $config->getBaseDn(),
                    'filter' => $filter,
                ]);

                // Fallback direct DN read (getUserByDn comme dans le projet de référence)
                if ($user->getDistinguishedName()) {
                    try {
                        $baseQuery = $ldap->query(
                            $user->getDistinguishedName(),
                            '(objectClass=person)',
                            ['scope' => QueryInterface::SCOPE_BASE]
                        );
                        $baseResults = iterator_to_array($baseQuery->execute());
                        $entry = !empty($baseResults) ? $baseResults[0] : null;
                    } catch (\Throwable $baseException) {
                        $this->logger->warning('Fallback lecture directe DN échouée: ' . $baseException->getMessage());
                    }
                }

                if (!$entry) {
                    throw $searchException;
                }
            }

            // Fallback : si aucune entrée trouvée via la recherche principale
            if (!$entry) {
                // Tenter par DN si disponible
                if ($user->getDistinguishedName()) {
                    try {
                        $baseQuery = $ldap->query(
                            $user->getDistinguishedName(),
                            '(objectClass=person)',
                            ['scope' => QueryInterface::SCOPE_BASE]
                        );
                        $baseResults = iterator_to_array($baseQuery->execute());
                        if (!empty($baseResults)) {
                            $entry = $baseResults[0];
                        }
                    } catch (\Throwable) {
                    }
                }

                // Tenter par sAMAccountName / uid si aucun filtre spécifique n'est configuré
                if (!$entry && ($configuredSearchFilter === null || $configuredSearchFilter === '')) {
                    try {
                        $fallbackFilter = sprintf('(&(objectClass=person)(|(sAMAccountName=%s)(uid=%s)))', $escapedTarget, $escapedTarget);
                        $fallbackQuery = $ldap->query($config->getBaseDn(), $fallbackFilter);
                        $fallbackResults = iterator_to_array($fallbackQuery->execute());
                        if (!empty($fallbackResults)) {
                            $entry = $fallbackResults[0];
                        }
                    } catch (\Throwable) {
                        // Ignorer l'échec du fallback
                    }
                }
            }

            if (!$entry) {
                return [
                    'success' => false,
                    'message' => sprintf('Utilisateur introuvable dans l\'annuaire LDAP (filtre: "%s").', $filter),
                ];
            }

            $extracted = $this->extractUserDataFromEntry($entry, $imageAttr, $emailAttr);
            $extracted['dn'] = $entry->getDn();

            // Vérification des filtres de recherche regex configurés
            if (!$this->matchesSearchFilters($config, $extracted, $entry)) {
                return [
                    'success' => false,
                    'message' => 'L\'utilisateur ne correspond pas aux filtres regex configurés dans les paramètres LDAP.',
                ];
            }

            if ($extracted['firstname'] !== null) {
                $user->setFirstName($extracted['firstname']);
            }
            if ($extracted['lastname'] !== null) {
                $user->setLastName($extracted['lastname']);
            }
            if ($extracted['displayName'] !== null) {
                $user->setDisplayName($extracted['displayName']);
            }
            if ($extracted['title'] !== null) {
                $user->setTitle($extracted['title']);
            }
            if ($extracted['department'] !== null) {
                $user->setDepartment($extracted['department']);
            }
            if ($extracted['managerDn'] !== null) {
                $user->setManagerDn($extracted['managerDn']);
            }

            // Gestion du manager (recherche ou création selon le managerDn)
            $managerDn = $extracted['managerDn'] ?? $user->getManagerDn();
            if ($managerDn !== null && trim($managerDn) !== '') {
                $manager = $this->findOrCreateManagerFromDn($managerDn, $config, $ldap);
                if ($manager && $manager !== $user && ($user->getId() === null || $manager->getId() !== $user->getId())) {
                    $user->setManager($manager);
                }
            }

            // Mise à jour de l'email si présent et modifié
            if ($extracted['email'] && $extracted['email'] !== $user->getEmail()) {
                $existingUser = $this->userRepository->findOneBy(['email' => $extracted['email']]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return [
                        'success' => false,
                        'message' => sprintf('L\'adresse email LDAP "%s" est déjà utilisée par un autre compte.', $extracted['email']),
                    ];
                }
                $user->setEmail($extracted['email']);
            }

            // Mise à jour de l'image (extraction base64)
            if ($extracted['image'] !== null) {
                $user->setImage($extracted['image']);
            }

            // Mise à jour du DN
            if ($entry->getDn()) {
                $user->setDistinguishedName($entry->getDn());
            }

            // Mise à jour de la date de synchronisation
            $user->setSyncedAt(new \DateTimeImmutable());

            // IMPORTANT : conformément à la consigne, nous ne modifions PAS le sAMAccountName (username ou ldapUid).
            // Le username et le ldapUid de l'utilisateur restent inchangés.

            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Informations utilisateur rafraîchies avec succès depuis le LDAP.',
                'user' => $user,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du rafraîchissement LDAP de l\'utilisateur: ' . $e->getMessage(), [
                'exception' => $e,
                'userId' => $user->getId(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement LDAP : ' . $e->getMessage(),
            ];
        }
    }

    public function verifyLdapPassword(LdapUser $user, string $password): bool
    {
        $config = $this->getActiveConfiguration();
        if (!$config || !$config->getHost()) {
            return false;
        }

        $dn = $user->getDistinguishedName();
        if (!$dn) {
            $dn = $this->findDnByIdentifier($config, $user->getUserIdentifier());
            if ($dn) {
                $user->setDistinguishedName($dn);
                $this->entityManager->flush();
            }
        }

        if (!$dn) {
            return false;
        }

        try {
            $ldap = $this->createLdapClient($config);
            $ldap->bind($dn, $password);

            $user->setSyncedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Échec de liaison LDAP pour utilisateur {email}: {error}', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function authenticateAndProvision(string $identifier, string $password): ?LdapUser
    {
        $config = $this->getActiveConfiguration();
        if (!$config || !$config->getHost() || !$config->getBaseDn()) {
            return null;
        }

        try {
            $ldap = $this->createLdapClient($config);
            if ($config->getBindDn() && $config->getBindPassword()) {
                $ldap->bind($config->getBindDn(), $config->getBindPassword());
            } else {
                $ldap->bind();
            }

            $escapedIdentifier = $ldap->escape($identifier, '', LdapInterface::ESCAPE_FILTER);
            $filter = sprintf('(|(mail=%s)(userPrincipalName=%s)(sAMAccountName=%s)(uid=%s))', $escapedIdentifier, $escapedIdentifier, $escapedIdentifier, $escapedIdentifier);

            $imageAttr = $config->getImageAttribute() ?: 'jpegPhoto';
            $emailAttr = $config->getEmailAttribute() ?: 'mail';
            $usernameAttr = $config->getUsernameAttribute() ?: 'sAMAccountName';

            $query = $ldap->query($config->getBaseDn(), $filter);
            $results = $query->execute();

            $entries = iterator_to_array($results);
            if (empty($entries)) {
                return null;
            }

            $entry = $entries[0];
            $userDn = $entry->getDn();
            if (!$userDn) {
                return null;
            }

            $extracted = $this->extractUserDataFromEntry($entry, $imageAttr, $emailAttr);
            $extracted['dn'] = $userDn;

            // Vérification des filtres de recherche regex avant bind
            if (!$this->matchesSearchFilters($config, $extracted, $entry)) {
                $this->logger->warning('LDAP JIT: authentification refusée car l\'utilisateur ne correspond pas aux filtres regex configurés: {identifier}', [
                    'identifier' => $identifier,
                ]);
                return null;
            }

            // Tenter le bind avec le mot de passe utilisateur
            try {
                $userLdap = $this->createLdapClient($config);
                $userLdap->bind($userDn, $password);
            } catch (\Throwable) {
                // Mot de passe incorrect
                return null;
            }

            // Bind réussi -> provisionner l'utilisateur
            $mail = $extracted['email'] ?? (str_contains($identifier, '@') ? $identifier : null);
            $uid = $extracted['username'] ?? $identifier;

            $email = $mail ?: ($uid . '@ldap.local');

            $existing = $this->userRepository->findOneBy(['email' => $email]);
            if ($existing instanceof LocalUser) {
                return null;
            }

            if ($existing instanceof LdapUser) {
                $existing->setDistinguishedName($userDn);
                $existing->setLdapUid($uid);
                $existing->setUsername($uid ?: $email);
                $existing->setSyncedAt(new \DateTimeImmutable());
                if ($extracted['firstname'] !== null) $existing->setFirstName($extracted['firstname']);
                if ($extracted['lastname'] !== null) $existing->setLastName($extracted['lastname']);
                if ($extracted['displayName'] !== null) $existing->setDisplayName($extracted['displayName']);
                if ($extracted['title'] !== null) $existing->setTitle($extracted['title']);
                if ($extracted['department'] !== null) $existing->setDepartment($extracted['department']);
                if ($extracted['managerDn'] !== null) $existing->setManagerDn($extracted['managerDn']);
                if ($extracted['image'] !== null) {
                    $existing->setImage($extracted['image']);
                }
                if ($extracted['managerDn'] !== null) {
                    $manager = $this->findOrCreateManagerFromDn($extracted['managerDn'], $config, $ldap);
                    if ($manager && $manager !== $existing && ($existing->getId() === null || $manager->getId() !== $existing->getId())) {
                        $existing->setManager($manager);
                    }
                }
                $this->entityManager->flush();

                return $existing;
            }

            $newUser = new LdapUser();
            $newUser->setEmail($email);
            $newUser->setUsername($uid ?: $email);
            $newUser->setDistinguishedName($userDn);
            $newUser->setLdapUid($uid);
            $newUser->setRoles(['ROLE_USER']);
            $newUser->setSyncedAt(new \DateTimeImmutable());
            if ($extracted['firstname'] !== null) $newUser->setFirstName($extracted['firstname']);
            if ($extracted['lastname'] !== null) $newUser->setLastName($extracted['lastname']);
            if ($extracted['displayName'] !== null) $newUser->setDisplayName($extracted['displayName']);
            if ($extracted['title'] !== null) $newUser->setTitle($extracted['title']);
            if ($extracted['department'] !== null) $newUser->setDepartment($extracted['department']);
            if ($extracted['managerDn'] !== null) $newUser->setManagerDn($extracted['managerDn']);
            if ($extracted['image'] !== null) {
                $newUser->setImage($extracted['image']);
            }
            if ($extracted['managerDn'] !== null) {
                $manager = $this->findOrCreateManagerFromDn($extracted['managerDn'], $config, $ldap);
                if ($manager && $manager !== $newUser && ($newUser->getId() === null || $manager->getId() !== $newUser->getId())) {
                    $newUser->setManager($manager);
                }
            }

            $this->entityManager->persist($newUser);
            $this->entityManager->flush();

            $this->logger->info('JIT LDAP: Utilisateur provisionné avec succès: {email}', ['email' => $email]);

            return $newUser;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du JIT LDAP: ' . $e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    private function findDnByIdentifier(LdapConfiguration $config, string $identifier): ?string
    {
        try {
            $ldap = $this->createLdapClient($config);
            if ($config->getBindDn() && $config->getBindPassword()) {
                $ldap->bind($config->getBindDn(), $config->getBindPassword());
            } else {
                $ldap->bind();
            }

            $escapedIdentifier = $ldap->escape($identifier, '', LdapInterface::ESCAPE_FILTER);
            $filter = sprintf('(|(mail=%s)(userPrincipalName=%s)(sAMAccountName=%s)(uid=%s))', $escapedIdentifier, $escapedIdentifier, $escapedIdentifier, $escapedIdentifier);

            $query = $ldap->query($config->getBaseDn(), $filter);
            $results = $query->execute();

            $entries = iterator_to_array($results);
            if (!empty($entries)) {
                return $entries[0]->getDn();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    protected function createLdapClient(LdapConfiguration $config): LdapInterface
    {
        if (\defined('LDAP_OPT_REFERRALS')) {
            @ldap_set_option(null, \LDAP_OPT_REFERRALS, 0);
        }
        if (\defined('LDAP_OPT_PROTOCOL_VERSION')) {
            @ldap_set_option(null, \LDAP_OPT_PROTOCOL_VERSION, 3);
        }

        return Ldap::create('ext_ldap', [
            'host' => $config->getHost(),
            'port' => (int) $config->getPort(),
            'encryption' => 'none',
            'options' => [
                'protocol_version' => 3,
                'referrals' => false,
                'network_timeout' => 10,
            ],
        ]);
    }

    /**
     * Extrait l'image d'une entrée LDAP et la retourne sous forme de chaîne base64 (sans préfixe data:).
     */
    public function extractImageFromEntry(Entry $entry, ?string $preferredAttribute = null): ?string
    {
        // 1. Si un attribut configuré dans le mapping est spécifié
        if ($preferredAttribute) {
            $val = $entry->getAttribute($preferredAttribute)
                ?? $entry->getAttribute(strtolower($preferredAttribute));
            if (!empty($val) && !empty($val[0])) {
                return $this->normalizeToBase64((string) $val[0]);
            }
        }

        $attributes = $entry->getAttributes();
        $normalizedAttributes = [];
        foreach ($attributes as $key => $values) {
            $normalizedAttributes[strtolower((string) $key)] = $values;
        }

        // 2. Attributs connus pour contenir une photo ou un avatar
        $candidateAttributes = [
            'jpegphoto',
            'thumbnailphoto',
            'photo',
            'picture',
            'avatar',
            'userphoto',
            'profilephoto',
            'image',
            'userimage',
            'displaypicture',
        ];

        foreach ($candidateAttributes as $attr) {
            if (!empty($normalizedAttributes[$attr])) {
                $rawValue = $normalizedAttributes[$attr][0] ?? null;
                if ($rawValue !== null && $rawValue !== '') {
                    return $this->normalizeToBase64((string) $rawValue);
                }
            }
        }

        // 2. Si non trouvé dans les attributs connus, parcourir les autres attributs pour détecter un contenu d'image
        foreach ($attributes as $name => $values) {
            $attrLower = strtolower((string) $name);
            if (in_array($attrLower, [
                'dn', 'mail', 'userprincipalname', 'samaccountname', 'uid', 'cn', 'sn',
                'givenname', 'displayname', 'userpassword', 'objectclass', 'distinguishedname',
                'memberof', 'telephonenumber', 'title', 'department', 'company', 'postalcode',
                'c', 'l', 'st', 'streetaddress',
            ], true)) {
                continue;
            }

            foreach ($values as $val) {
                if (!is_string($val) || $val === '') {
                    continue;
                }

                if ($this->isImageContent($val)) {
                    return $this->normalizeToBase64($val);
                }
            }
        }

        return null;
    }

    public function isImageContent(string $data): bool
    {
        // 1. Signatures binaires
        if (str_starts_with($data, "\xFF\xD8\xFF")) { // JPEG
            return true;
        }
        if (str_starts_with($data, "\x89PNG\r\n\x1a\n") || str_starts_with($data, "\x89PNG")) { // PNG
            return true;
        }
        if (str_starts_with($data, 'GIF87a') || str_starts_with($data, 'GIF89a')) { // GIF
            return true;
        }
        if (str_starts_with($data, 'RIFF') && strlen($data) > 12 && substr($data, 8, 4) === 'WEBP') { // WebP
            return true;
        }
        if (str_starts_with($data, '<svg') || (str_starts_with($data, '<?xml') && str_contains($data, '<svg'))) { // SVG
            return true;
        }

        // 2. Data URL
        if (str_starts_with($data, 'data:image/')) {
            return true;
        }

        // 3. Chaîne base64 reconnue
        $trimmed = trim($data);
        if (
            str_starts_with($trimmed, '/9j/') // JPEG base64
            || str_starts_with($trimmed, 'iVBORw0K') // PNG base64
            || str_starts_with($trimmed, 'R0lGOD') // GIF base64
            || str_starts_with($trimmed, 'R0lG') // GIF base64
            || str_starts_with($trimmed, 'UklGR') // WebP base64
        ) {
            return true;
        }

        return false;
    }

    public function normalizeToBase64(string $data): string
    {
        $trimmed = trim($data);

        // Si c'est déjà un Data URL (data:image/...;base64,xxxx), extraire la charge base64
        if (preg_match('/^data:image\/[a-zA-Z0-9\+\.\-]+;base64,(.+)$/s', $trimmed, $matches)) {
            return trim($matches[1]);
        }

        // Si c'est déjà du base64 image valide
        if (
            str_starts_with($trimmed, '/9j/')
            || str_starts_with($trimmed, 'iVBORw0K')
            || str_starts_with($trimmed, 'R0lGOD')
            || str_starts_with($trimmed, 'R0lG')
            || str_starts_with($trimmed, 'UklGR')
        ) {
            return $trimmed;
        }

        // Sinon, encoder les données brutes (binaires ou SVG) en base64
        return base64_encode($data);
    }

    /**
     * Vérifie si une chaîne correspond à une expression régulière.
     * Gère avec tolérance les motifs avec ou sans délimiteurs (/.../, #...#).
     */
    public function matchesRegex(string $pattern, ?string $value): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return true;
        }

        if ($value === null) {
            return false;
        }

        // Si le motif est déjà délimité par des slashs ou autres délimiteurs valides
        if (preg_match('/^([\/#~%]).*\1[imsxADSUXJu]*$/s', $pattern)) {
            $regex = $pattern;
        } else {
            // Délimiteur par défaut '/' avec flag 'i' (insensible à la casse)
            $escaped = str_replace('/', '\/', $pattern);
            $regex = '/' . $escaped . '/i';
        }

        try {
            $result = @preg_match($regex, $value);
            if ($result === false) {
                $this->logger->warning('Expression régulière LDAP invalide ignorée: ' . $pattern);
                return true;
            }

            return $result === 1;
        } catch (\Throwable $e) {
            $this->logger->warning('Erreur d\'évaluation regex LDAP: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Vérifie si les données d'un utilisateur LDAP correspondent aux filtres de recherche regex configurés.
     *
     * @param array<string, mixed> $userData
     */
    public function matchesSearchFilters(LdapConfiguration $config, array $userData, ?Entry $entry = null): bool
    {
        // 0. Vérification de la regex de requête (queryRegex) si configurée
        $queryRegex = trim((string) ($config->getQueryRegex() ?? ''));
        if ($queryRegex !== '') {
            $candidateValues = array_filter([
                $userData['username'] ?? null,
                $userData['email'] ?? null,
                $userData['displayName'] ?? null,
                $userData['firstname'] ?? null,
                $userData['lastname'] ?? null,
                $userData['dn'] ?? ($entry?->getDn()),
            ], fn($v) => is_string($v) && $v !== '');

            $matchedQueryRegex = false;
            foreach ($candidateValues as $val) {
                if ($this->matchesRegex($queryRegex, $val)) {
                    $matchedQueryRegex = true;
                    break;
                }
            }

            if (!$matchedQueryRegex) {
                return false;
            }
        }

        // 1. Vérification du filtre regex global (searchFilterRegex)
        $globalPattern = trim((string) ($config->getSearchFilterRegex() ?? ''));
        if ($globalPattern !== '') {
            $candidateValues = array_filter([
                $userData['username'] ?? null,
                $userData['email'] ?? null,
                $userData['displayName'] ?? null,
                $userData['firstname'] ?? null,
                $userData['lastname'] ?? null,
                $userData['department'] ?? null,
                $userData['title'] ?? null,
                $userData['dn'] ?? ($entry?->getDn()),
            ], fn($v) => is_string($v) && $v !== '');

            $matchedGlobal = false;
            foreach ($candidateValues as $val) {
                if ($this->matchesRegex($globalPattern, $val)) {
                    $matchedGlobal = true;
                    break;
                }
            }

            if (!$matchedGlobal) {
                return false;
            }
        }

        // 2. Vérification des filtres de recherche spécifiques configurés
        $filters = $config->getSearchFilters();
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $key => $filter) {
                $attr = '';
                $pattern = '';

                if (is_array($filter)) {
                    $attr = trim((string) ($filter['attribute'] ?? $filter['field'] ?? ''));
                    $pattern = trim((string) ($filter['pattern'] ?? $filter['regex'] ?? ''));
                } elseif (is_string($filter)) {
                    $attr = is_string($key) ? trim($key) : '';
                    $pattern = trim($filter);
                }

                if ($pattern === '') {
                    continue;
                }

                $attrLower = strtolower($attr);

                if ($attrLower === '' || $attrLower === '*' || $attrLower === 'all') {
                    // Doit correspondre à au moins un attribut principal
                    $candidateValues = array_filter([
                        $userData['username'] ?? null,
                        $userData['email'] ?? null,
                        $userData['displayName'] ?? null,
                        $userData['dn'] ?? ($entry?->getDn()),
                        $userData['department'] ?? null,
                        $userData['title'] ?? null,
                    ], fn($v) => is_string($v) && $v !== '');

                    $matchedAny = false;
                    foreach ($candidateValues as $val) {
                        if ($this->matchesRegex($pattern, $val)) {
                            $matchedAny = true;
                            break;
                        }
                    }
                    if (!$matchedAny) {
                        return false;
                    }
                    continue;
                }

                // Cible un attribut spécifique
                $valueToTest = null;
                switch ($attrLower) {
                    case 'mail':
                    case 'email':
                    case 'userprincipalname':
                        $valueToTest = $userData['email'] ?? null;
                        break;
                    case 'samaccountname':
                    case 'username':
                    case 'uid':
                        $valueToTest = $userData['username'] ?? null;
                        break;
                    case 'displayname':
                    case 'name':
                        $valueToTest = $userData['displayName'] ?? null;
                        break;
                    case 'givenname':
                    case 'firstname':
                        $valueToTest = $userData['firstname'] ?? null;
                        break;
                    case 'sn':
                    case 'lastname':
                        $valueToTest = $userData['lastname'] ?? null;
                        break;
                    case 'dn':
                    case 'distinguishedname':
                        $valueToTest = $userData['dn'] ?? ($entry?->getDn());
                        break;
                    case 'department':
                        $valueToTest = $userData['department'] ?? null;
                        break;
                    case 'title':
                        $valueToTest = $userData['title'] ?? null;
                        break;
                    default:
                        if ($entry) {
                            $vals = $entry->getAttribute($attr) ?? $entry->getAttribute($attrLower);
                            $valueToTest = !empty($vals) ? (string) $vals[0] : null;
                        }
                        break;
                }

                if ($valueToTest === null || !$this->matchesRegex($pattern, (string) $valueToTest)) {
                    return false;
                }
            }
        }

        return true;
    }
}
