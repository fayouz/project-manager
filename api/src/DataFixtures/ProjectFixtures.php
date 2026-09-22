<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Integration;
use App\Entity\Organisation;
use App\Entity\Project;
use App\Entity\ProjectIntegration;
use App\Entity\Server;
use App\Entity\ServerAuthenticationType;
use App\Entity\ServerType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $bmeOrgansation  = new Organisation();
        $bmeOrgansation->setName('BME');
        $manager->persist($bmeOrgansation);

        $scrapperProject = new Project();
        $scrapperProject->setName('Scrapper');
        $scrapperProject->setOrganisation($bmeOrgansation);
        $manager->persist($scrapperProject);

        $giteaIntegration = $manager->getRepository(Integration::class)->findOneBy(['type' => 'gitea']);
        if ($giteaIntegration !== null) {
            $scrapperGitea = new ProjectIntegration();
            $scrapperGitea->setProject($scrapperProject);
            $scrapperGitea->setIntegration($giteaIntegration);
            $scrapperGitea->setParameters([
                'repository' => 'bm-energies/scrapper',
            ]);
            $manager->persist($scrapperGitea);
        }

        $sonarIntegration = $manager->getRepository(Integration::class)->findOneBy(['type' => 'sonarqube']);
        if ($sonarIntegration !== null) {
            $scrapperSonar = new ProjectIntegration();
            $scrapperSonar->setProject($scrapperProject);
            $scrapperSonar->setIntegration($sonarIntegration);
            $scrapperSonar->setParameters([
                'project_key' => 'bme.scrapper',
            ]);
            $manager->persist($scrapperSonar);
        }

        $nexusIntegration = $manager->getRepository(Integration::class)->findOneBy(['type' => 'nexus']);
        if ($nexusIntegration !== null) {
            $scrapperNexus = new ProjectIntegration();
            $scrapperNexus->setProject($scrapperProject);
            $scrapperNexus->setIntegration($nexusIntegration);
            $scrapperNexus->setParameters([
                'repository' => 'maven-releases',
            ]);
            $manager->persist($scrapperNexus);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            IntegrationFixtures::class,
        ];
    }
}
