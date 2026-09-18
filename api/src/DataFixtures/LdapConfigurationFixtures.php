<?php

namespace App\DataFixtures;

use App\Entity\LdapConfiguration;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LdapConfigurationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $config = new LdapConfiguration();
        $config->setEnabled(false);
        $config->setHost('ldap.example.com');
        $config->setPort(389);
        $config->setBaseDn('dc=example,dc=com');
        $config->setBindDn('cn=admin,dc=example,dc=com');
        $config->setBindPassword('password');

        $manager->persist($config);
        $manager->flush();
    }
}
