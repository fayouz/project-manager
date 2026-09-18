<?php

namespace App\DataFixtures;

use App\Entity\LdapServer;
use App\Entity\Server;
use App\Entity\ServerType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LdapServerFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Créer ou obtenir le type LDAP
        $ldapType = $manager->getRepository(ServerType::class)->findOneBy(['name' => 'LDAP']);

        if ($ldapType === null) {
            $ldapType = new ServerType();
            $ldapType->setName('LDAP');
            $manager->persist($ldapType);
            $manager->flush(); // Sauvegarder immédiatement pour obtenir l'ID
        }

        $ldapServer = new Server();
        $ldapServer->setPort(389);
        $ldapServer->setUsername('VISION');
        $ldapServer->setPassword('gdb100');
        $ldapServer->setName('regaz');
        $ldapServer->setHost('ldap.groupegdb.local');
        $ldapServer->setType($ldapType);
        $ldapServer->setOptions([
            'base_dn' => 'dc=groupegdb,dc=local',
            'dn_string' => 'dc=groupegdb,dc=local',
            'search_dn' => 'cn=VISION,cn=Users,dc=groupegdb,dc=local',
            'search_password' => 'gdb100'
        ]);

        $manager->persist($ldapServer);
        $manager->flush();
    }
}
