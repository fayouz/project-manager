<?php

namespace App\Test;

use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\Panther\PantherTestCase;

class ProjectTest extends PantherTestCase
{
    private const SESSION_TITLE = 'Integrate (Vue)JS components in a Symfony app, add E2E tests with Panther';
    use ReloadDatabaseTrait; // From AliceBundle, reloads the fixtures before every test

    public function testComment()
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/projects/1');


        $client->waitFor('#projectTitle');
        $this->assertSame('Projects', $crawler->filter('h1:first-of-type')->text());
        $this->assertSame('Test1', $crawler->filter('h1:nth-child(2)')->text());
        $client->takeScreenshot('/tmp/panther.png');
    }
}