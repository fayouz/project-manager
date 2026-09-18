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

            $query = $ldap->query($config->getBaseDn(), '(&(objectClass=person)(|(mail=*)(sAMAccountName=*)(uid=*)))');
            $results = $query->execute();

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($results as $entry) {
                $mail = $entry->getAttribute('mail')[0]
                    ?? $entry->getAttribute('userPrincipalName')[0]
                    ?? null;
                $uid = $entry->getAttribute('sAMAccountName')[0]
                    ?? $entry->getAttribute('uid')[0]
                    ?? null;
                $dn = $entry->getDn();

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
                    $updated++;
                } else {
                    $newUser = new LdapUser();
                    $newUser->setEmail($email);
                    $newUser->setUsername($uid ?: $email);
                    $newUser->setDistinguishedName($dn);
                    $newUser->setLdapUid($uid);
                    $newUser->setRoles(['ROLE_USER']);
                    $newUser->setSyncedAt(new \DateTimeImmutable());

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

            // Tenter le bind avec le mot de passe utilisateur
            try {
                $userLdap = $this->createLdapClient($config);
                $userLdap->bind($userDn, $password);
            } catch (\Throwable) {
                // Mot de passe incorrect
                return null;
            }

            // Bind réussi -> provisionner l'utilisateur
            $mail = $entry->getAttribute('mail')[0]
                ?? $entry->getAttribute('userPrincipalName')[0]
                ?? (str_contains($identifier, '@') ? $identifier : null);
            $uid = $entry->getAttribute('sAMAccountName')[0]
                ?? $entry->getAttribute('uid')[0]
                ?? $identifier;

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

    private function createLdapClient(LdapConfiguration $config): Ldap
    {
        return Ldap::create('ext_ldap', [
            'host' => $config->getHost(),
            'port' => (int) $config->getPort(),
            'encryption' => 'none',
        ]);
    }
}
