<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Repository\UserRepository;
use App\Service\LdapSyncService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class HybridJsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LdapSyncService $ldapSyncService,
        #[Autowire(service: 'lexik_jwt_authentication.handler.authentication_success')]
        private readonly AuthenticationSuccessHandlerInterface $successHandler,
        #[Autowire(service: 'lexik_jwt_authentication.handler.authentication_failure')]
        private readonly AuthenticationFailureHandlerInterface $failureHandler,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/api/login_check';
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadCredentialsException('Corps de requête JSON invalide.');
        }

        $email = $data['email'] ?? $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !is_string($email) || !$password || !is_string($password)) {
            throw new BadCredentialsException('Email et mot de passe requis.');
        }

        // 1. Recherche dans la base locale par email ou username
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $user = $this->userRepository->findOneBy(['username' => $email]);
        }

        // Cas 1: Utilisateur local -> vérification standard par hash
        if ($user instanceof LocalUser) {
            return new Passport(
                new UserBadge($user->getUserIdentifier(), fn() => $user),
                new PasswordCredentials($password)
            );
        }

        // Cas 2: Utilisateur LDAP existant en base -> vérification en direct sur LDAP
        if ($user instanceof LdapUser) {
            if (!$this->ldapSyncService->verifyLdapPassword($user, $password)) {
                throw new BadCredentialsException('Identifiants LDAP invalides.');
            }

            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier(), fn() => $user)
            );
        }

        // Cas 3: Utilisateur non présent en base locale -> recherche et JIT provisioning LDAP
        $ldapUser = $this->ldapSyncService->authenticateAndProvision($email, $password);
        if ($ldapUser) {
            return new SelfValidatingPassport(
                new UserBadge($ldapUser->getUserIdentifier(), fn() => $ldapUser)
            );
        }

        throw new BadCredentialsException('Identifiants invalides.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }
}
