<?php

namespace App\DataFixtures;

use App\Entity\ServerAuthenticationType;
use App\Entity\ServerType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = [
            'LDAP',
            'HTTP',
            'FTP',
            'SMTP',
        ];

        foreach ($types as $typeName) {
            $serverType = new ServerType();
            $serverType->setName($typeName);
            $manager->persist($serverType);
        }

        $authTypes = [
            'Basic',
            'Token',
            'Aucune',
        ];

        foreach ($authTypes as $authTypeName) {
            $authType = new ServerAuthenticationType();
            $authType->setName($authTypeName);
            $manager->persist($authType);
        }

        $manager->flush();
    }
}
