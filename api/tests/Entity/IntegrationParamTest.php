<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\GiteaIntegrationParam;
use App\Entity\IntegrationParamFactory;
use App\Entity\JenkinsIntegrationParam;
use App\Entity\MantisIntegrationParam;
use App\Entity\SonarQubeIntegrationParam;
use PHPUnit\Framework\TestCase;

class IntegrationParamTest extends TestCase
{
    public function testMantisIntegrationParam(): void
    {
        $param = new MantisIntegrationParam();
        $param->setProjectId(438);
        $param->setProjectName('RegazScrapper');

        $this->assertSame('mantis', $param->getType());
        $this->assertSame('438', $param->getProjectId());
        $this->assertSame('RegazScrapper', $param->getProjectName());
        $this->assertSame('RegazScrapper (#438)', $param->getTargetDisplay());
        $this->assertTrue($param->isValid());

        $array = $param->toArray();
        $this->assertSame('438', $array['project_id']);
        $this->assertSame('RegazScrapper', $array['project_name']);

        $restored = MantisIntegrationParam::fromArray($array);
        $this->assertSame('438', $restored->getProjectId());
        $this->assertSame('RegazScrapper', $restored->getProjectName());

        $emptyParam = new MantisIntegrationParam();
        $this->assertFalse($emptyParam->isValid());
        $this->assertSame('Non configuré', $emptyParam->getTargetDisplay());
        $this->assertArrayHasKey('project_id', $emptyParam->validate());
    }

    public function testGiteaIntegrationParam(): void
    {
        $param = new GiteaIntegrationParam();
        $param->setRepository('bm-energies/scrapper');
        $param->setBranch('main');

        $this->assertSame('gitea', $param->getType());
        $this->assertSame('bm-energies/scrapper', $param->getRepository());
        $this->assertSame('main', $param->getBranch());
        $this->assertSame('bm-energies/scrapper (main)', $param->getTargetDisplay());
        $this->assertTrue($param->isValid());

        $restored = GiteaIntegrationParam::fromArray($param->toArray());
        $this->assertSame('bm-energies/scrapper', $restored->getRepository());
        $this->assertSame('main', $restored->getBranch());
    }

    public function testSonarQubeIntegrationParam(): void
    {
        $param = new SonarQubeIntegrationParam();
        $param->setProjectKey('rgz.regazscrappers');

        $this->assertSame('sonarqube', $param->getType());
        $this->assertSame('rgz.regazscrappers', $param->getProjectKey());
        $this->assertSame('rgz.regazscrappers', $param->getTargetDisplay());
        $this->assertTrue($param->isValid());

        $restored = SonarQubeIntegrationParam::fromArray($param->toArray());
        $this->assertSame('rgz.regazscrappers', $restored->getProjectKey());
    }

    public function testJenkinsIntegrationParam(): void
    {
        $param = new JenkinsIntegrationParam();
        $param->setJobName('build-pipeline');

        $this->assertSame('jenkins', $param->getType());
        $this->assertSame('build-pipeline', $param->getJobName());
        $this->assertSame('build-pipeline', $param->getTargetDisplay());
        $this->assertTrue($param->isValid());

        $restored = JenkinsIntegrationParam::fromArray(['job' => 'build-pipeline']);
        $this->assertSame('build-pipeline', $restored->getJobName());

        // Test mode dossier Jenkins
        $folderParam = new JenkinsIntegrationParam();
        $folderParam->setFolder('REGAZ/RegazScrapper');
        $this->assertSame('job/REGAZ/job/RegazScrapper/', $folderParam->getFolder());
        $this->assertSame('job/REGAZ/job/RegazScrapper/', $folderParam->getTargetDisplay());
        $this->assertTrue($folderParam->isValid());

        $folderParam->setJobs([
            ['name' => '1.1.1_Build_TU', 'status' => 'SUCCESS'],
            ['name' => '1.1.2_Build_TI', 'status' => 'SUCCESS'],
        ]);
        $this->assertSame(2, $folderParam->getJobsCount());
        $this->assertSame('job/REGAZ/job/RegazScrapper/ (2 jobs)', $folderParam->getTargetDisplay());

        $array = $folderParam->toArray();
        $this->assertSame('job/REGAZ/job/RegazScrapper/', $array['folder']);
        $this->assertCount(2, $array['jobs']);
        $this->assertSame(2, $array['jobs_count']);

        $restoredFolder = JenkinsIntegrationParam::fromArray($array);
        $this->assertSame('job/REGAZ/job/RegazScrapper/', $restoredFolder->getFolder());
        $this->assertSame(2, $restoredFolder->getJobsCount());
    }

    public function testFactory(): void
    {
        $mantis = IntegrationParamFactory::create('mantis', ['project_id' => '12', 'project_name' => 'Demo']);
        $this->assertInstanceOf(MantisIntegrationParam::class, $mantis);
        $this->assertSame('Demo (#12)', $mantis->getTargetDisplay());

        $gitea = IntegrationParamFactory::create('gitea', ['repository' => 'a/b']);
        $this->assertInstanceOf(GiteaIntegrationParam::class, $gitea);
        $this->assertSame('a/b', $gitea->getTargetDisplay());

        $sonar = IntegrationParamFactory::create('sonarqube', ['project_key' => 'xyz']);
        $this->assertInstanceOf(SonarQubeIntegrationParam::class, $sonar);
        $this->assertSame('xyz', $sonar->getTargetDisplay());

        $jenkins = IntegrationParamFactory::create('jenkins', ['job_name' => 'test-job']);
        $this->assertInstanceOf(JenkinsIntegrationParam::class, $jenkins);
        $this->assertSame('test-job', $jenkins->getTargetDisplay());
    }
}
