<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Repository\LdapConfigurationRepository;
use App\Repository\UserRepository;
use App\Service\LdapSyncService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Entry;

class LdapImageSyncTest extends TestCase
{
    private LdapSyncService $service;

    protected function setUp(): void
    {
        $ldapConfigRepo = $this->createMock(LdapConfigurationRepository::class);
        $userRepo = $this->createMock(UserRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->service = new LdapSyncService($ldapConfigRepo, $userRepo, $em, $logger);
    }

    public function testExtractJpegFromJpegPhotoAttribute(): void
    {
        $jpegBinary = "\xFF\xD8\xFF\xE0\x00\x10JFIF" . str_repeat('A', 20);
        $entry = new Entry('cn=john,dc=example,dc=org', [
            'jpegPhoto' => [$jpegBinary],
        ]);

        $base64 = $this->service->extractImageFromEntry($entry);
        $this->assertNotNull($base64);
        $this->assertSame(base64_encode($jpegBinary), $base64);
    }

    public function testExtractFromCustomMappedAttribute(): void
    {
        $pngBinary = "\x89PNG\r\n\x1a\n" . str_repeat('M', 20);
        $entry = new Entry('cn=mapped,dc=example,dc=org', [
            'customPhotoAttr' => [$pngBinary],
            'mail' => ['mapped@example.org'],
        ]);

        // When specifying the custom mapped attribute name
        $base64 = $this->service->extractImageFromEntry($entry, 'customPhotoAttr');
        $this->assertNotNull($base64);
        $this->assertSame(base64_encode($pngBinary), $base64);
    }

    public function testExtractPngFromThumbnailPhotoAttribute(): void
    {
        $pngBinary = "\x89PNG\r\n\x1a\n" . str_repeat('B', 20);
        $entry = new Entry('cn=alice,dc=example,dc=org', [
            'thumbnailphoto' => [$pngBinary],
        ]);

        $base64 = $this->service->extractImageFromEntry($entry);
        $this->assertNotNull($base64);
        $this->assertSame(base64_encode($pngBinary), $base64);
    }

    public function testExtractFromDataUrl(): void
    {
        $pngBinary = "\x89PNG\r\n\x1a\n" . str_repeat('C', 20);
        $dataUrl = 'data:image/png;base64,' . base64_encode($pngBinary);
        $entry = new Entry('cn=bob,dc=example,dc=org', [
            'avatar' => [$dataUrl],
        ]);

        $base64 = $this->service->extractImageFromEntry($entry);
        $this->assertNotNull($base64);
        $this->assertSame(base64_encode($pngBinary), $base64);
    }

    public function testExtractFromArbitraryAttributeWhenImageDetected(): void
    {
        $gifBinary = "GIF89a" . str_repeat('D', 20);
        $entry = new Entry('cn=eve,dc=example,dc=org', [
            'customFieldPhoto' => [$gifBinary],
            'mail' => ['eve@example.org'],
        ]);

        $base64 = $this->service->extractImageFromEntry($entry);
        $this->assertNotNull($base64);
        $this->assertSame(base64_encode($gifBinary), $base64);
    }

    public function testReturnNullWhenNoImagePresent(): void
    {
        $entry = new Entry('cn=noname,dc=example,dc=org', [
            'mail' => ['noname@example.org'],
            'cn' => ['No Name'],
            'sAMAccountName' => ['noname'],
        ]);

        $base64 = $this->service->extractImageFromEntry($entry);
        $this->assertNull($base64);
    }

    public function testUserAvatarGeneration(): void
    {
        $user = new LdapUser();
        $this->assertNull($user->getAvatar());

        // Test with raw base64 JPEG
        $jpegBinary = "\xFF\xD8\xFF\xE0" . str_repeat('X', 10);
        $user->setImage(base64_encode($jpegBinary));
        $this->assertStringStartsWith('data:image/jpeg;base64,', (string) $user->getAvatar());

        // Test with raw base64 PNG
        $pngBinary = "\x89PNG\r\n\x1a\n" . str_repeat('Y', 10);
        $user->setImage(base64_encode($pngBinary));
        $this->assertStringStartsWith('data:image/png;base64,', (string) $user->getAvatar());

        // Test with already full data URL
        $user->setImage('data:image/webp;base64,AAAA');
        $this->assertSame('data:image/webp;base64,AAAA', $user->getAvatar());

        // Test with http URL
        $user->setImage('https://example.com/avatar.png');
        $this->assertSame('https://example.com/avatar.png', $user->getAvatar());
    }
}
