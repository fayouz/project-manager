<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Integration;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Entity\ServerType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class IntegrationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $httpType = $manager->getRepository(ServerType::class)->findOneBy(['name' => 'HTTP']);
        if ($httpType === null) {
            $httpType = new ServerType();
            $httpType->setName('HTTP');
            $manager->persist($httpType);
            $manager->flush();
        }

        $basicAuth = $manager->getRepository(ServerAuthenticationType::class)->findOneBy(['name' => 'Basic']);
        $tokenAuth = $manager->getRepository(ServerAuthenticationType::class)->findOneBy(['name' => 'Token']);

        // 1. Jenkins Server & Integration
        $jenkinsServer = new Server();
        $jenkinsServer->setName('Jenkins Master Server');
        $jenkinsServer->setHost('jenkins.bm-energies.com');
        $jenkinsServer->setPort(8080);
        $jenkinsServer->setUsername('pinf14');
        $jenkinsServer->setPassword('IoNa/Dev//1980!');
        $jenkinsServer->setType($httpType);
        $jenkinsServer->setAuthenticationType($basicAuth);
        $jenkinsServer->setOptions(['protocol' => 'http']);
        $manager->persist($jenkinsServer);

        $jenkins = new Integration();
        $jenkins->setName('Jenkins CI');
        $jenkins->setType('jenkins');
        $jenkins->setEnabled(true);
        $jenkins->setStatus('unknown');
        $jenkins->setServer($jenkinsServer);
        $manager->persist($jenkins);

        // 2. Gitea Server & Integration
        $giteaServer = new Server();
        $giteaServer->setName('Internal Git Server');
        $giteaServer->setHost('gitea.bm-energies.com');
        $giteaServer->setPort(3000);
        $giteaServer->setUsername('PINF14');
        $giteaServer->setPassword('IoNa/Dev//1980!');
        $giteaServer->setType($httpType);
        $giteaServer->setAuthenticationType($basicAuth);
        $giteaServer->setOptions([
            'protocol' => 'http',
            'proxy' => 'http://px.groupegdb.local:8080',
        ]);
        $manager->persist($giteaServer);

        $gitea = new Integration();
        $gitea->setName('Gitea Forge');
        $gitea->setType('gitea');
        $gitea->setEnabled(true);
        $gitea->setStatus('unknown');
        $gitea->setServer($giteaServer);
        $manager->persist($gitea);

        // 3. Mantis Server & Integration
        $mantisServer = new Server();
        $mantisServer->setName('Mantis Bug Tracker');
        $mantisServer->setHost('mantis.bm-energies.com');
        $mantisServer->setPort(443);
        $mantisServer->setUsername('PINF14');
        $mantisServer->setPassword('IoNa/Dev//1980!');
        $mantisServer->setType($httpType);
        $mantisServer->setAuthenticationType($basicAuth);
        $mantisServer->setOptions([
            'protocol' => 'https',
        ]);
        $manager->persist($mantisServer);

        $mantis = new Integration();
        $mantis->setName('Mantis Bug Tracker');
        $mantis->setType('mantis');
        $mantis->setEnabled(true);
        $mantis->setStatus('unknown');
        $mantis->setServer($mantisServer);
        $manager->persist($mantis);

        // 4. SonarQube Server & Integration
        $sonarServer = new Server();
        $sonarServer->setName('SonarQube Code Quality');
        $sonarServer->setHost('sonarqube.bm-energies.com');
        $sonarServer->setPort(9000);
        $sonarServer->setUsername('PINF14');
        $sonarServer->setPassword('IoNa/Dev//1980!');
        $sonarServer->setType($httpType);
        $sonarServer->setAuthenticationType($basicAuth);
        $sonarServer->setOptions([
            'protocol' => 'http',
        ]);
        $manager->persist($sonarServer);

        $sonar = new Integration();
        $sonar->setName('SonarQube');
        $sonar->setType('sonarqube');
        $sonar->setEnabled(true);
        $sonar->setStatus('unknown');
        $sonar->setServer($sonarServer);
        $manager->persist($sonar);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
}
