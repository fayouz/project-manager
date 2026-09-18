<?php
// src/Command/SyncLdapUsersCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Ldap;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Server;
use Symfony\Component\Ldap\LdapInterface;

class SyncLdapUsersCommand extends Command
{
    protected static $defaultName = 'app:sync-ldap-users';
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * Configure la commande avec une option pour spécifier un serveur LDAP
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Synchronise les utilisateurs LDAP avec la base de données locale')
            ->setHelp('Cette commande interroge un serveur LDAP pour récupérer les utilisateurs et les insérer dans la base de données.')
            ->addOption(
                'server',
                null,
                InputOption::VALUE_REQUIRED,
                'Spécifiez l\'ID ou le nom du serveur LDAP à utiliser'
            );
    }

    /**
     * Exécute la commande de synchronisation LDAP
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer l'identifiant du serveur
        $serverIdentifier = $input->getOption('server');
        if (!$serverIdentifier) {
            $io->error('Vous devez spécifier un serveur LDAP via l\'option --server.');
            return Command::FAILURE;
        }

        // Recherche du serveur dans la base de données
        $io->section("Chargement du serveur LDAP : $serverIdentifier");
        $server = $this->findServer($serverIdentifier, $io);
        if (!$server) {
            return Command::FAILURE;
        }

        // Vérifier que le serveur est de type LDAP
        if ($server->getType()->getName() !== 'LDAP') {
            $io->error("Le serveur spécifié ({$server->getName()}) n'est pas de type LDAP.");
            return Command::FAILURE;
        }

        // Récupération des informations de connexion LDAP
        $ldapHost = $server->getHost();
        $ldapPort = $server->getPort();
        $ldapUser = $server->getUsername();
        $ldapPassword = $server->getPassword();
        $options = $server->getOptions();

        if (!isset($options['base_dn'])) {
            $io->error("Le paramètre 'base_dn' est manquant pour le serveur LDAP {$server->getName()}.");
            return Command::FAILURE;
        }

        $baseDn = $options['base_dn'];
        $io->text("Connexion au serveur LDAP : {$server->getName()} ($ldapHost:$ldapPort)");


        try {
            //Is server reachable
            

            // Création et configuration de l'instance LDAP
            $ldap = Ldap::create('ext_ldap', [
                'host' => $ldapHost,
                'port' => $ldapPort
            ]);

            $ldap->bind('cn=VISION,cn=Users,dc=groupegdb,dc=local', 'gdb100');
            dd('faez');


            // Authentification LDAP
            if ($ldapUser && $ldapPassword) {
                $ldap->bind($ldapUser, $ldapPassword);
            } else {
                $ldap->bind(); // Liaison anonyme si aucun utilisateur/mdp n'est fourni
            }
        } catch (\Exception $e) {
            $io->error("Erreur lors de la connexion au serveur LDAP : " . $e->getMessage());
            return Command::FAILURE;
        }



        // Requête LDAP
        $query = $ldap->query($baseDn, '(objectClass=person)'); // Filtre LDAP par défaut


        $identifier = $this->ldap->escape($identifier, '', LdapInterface::ESCAPE_FILTER);


        $uidKey = 'sAMAccountName';
        $filter = '({uid_key}={user_identifier})';
        $defaultSearch = str_replace('{uid_key}', $uidKey, $filter);
        $query = str_replace('{username}', '{user_identifier}', $defaultSearch, $replaceCount);
        $query = str_replace('{user_identifier}', $identifier, $query);
        $search = $ldap->query($baseDn, $query);
        try {
            $results = $search->execute();
        } catch (\Exception $e) {
            $io->error("Erreur lors de l'exécution de la requête LDAP : " . $e->getMessage());
            return Command::FAILURE;
        }

        // Synchronisation des utilisateurs
        $io->section('Synchronisation des utilisateurs LDAP...');
        $this->synchronizeUsersFromLdap($results, $io);

        $io->success('La synchronisation LDAP a été effectuée avec succès.');
        return Command::SUCCESS;
    }

    /**
     * Synchronise les utilisateurs depuis LDAP
     */
    private function synchronizeUsersFromLdap(iterable $results, SymfonyStyle $io): void
    {
        $count = 0;

        foreach ($results as $entry) {
            $uid = $entry->getAttribute('uid')[0] ?? null;
            $email = $entry->getAttribute('mail')[0] ?? null;
            $firstName = $entry->getAttribute('givenName')[0] ?? null;
            $lastName = $entry->getAttribute('sn')[0] ?? null;

            if (!$uid) {
                $io->warning('Utilisateur avec un UID manquant ignoré.');
                continue;
            }

            // Recherche d'un utilisateur existant par UID
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $uid]);

            if (!$user) {
                // Créer un nouvel utilisateur
                $user = new User();
                $user->setUsername($uid);
                $io->text("Nouvel utilisateur ajouté : $uid");
            } else {
                $io->text("Mise à jour de l'utilisateur : $uid");
            }

            // Mettre à jour les informations de l'utilisateur
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles(['ROLE_USER']); // Rôle par défaut

            $this->entityManager->persist($user);
            $count++;
        }

        // Sauvegarder en base de données
        $this->entityManager->flush();

        $io->success("$count utilisateur(s) synchronisé(s).");
    }

    /**
     * Recherche un serveur par ID ou par nom
     */
    private function findServer(string $identifier, SymfonyStyle $io): ?Server
    {
        $server = $this->entityManager->getRepository(Server::class)->findOneBy(['name' => $identifier]);

        if (!$server) {
            $io->error("Aucun serveur trouvé avec l'identifiant ou le nom : $identifier");
        }

        return $server;
    }
}
