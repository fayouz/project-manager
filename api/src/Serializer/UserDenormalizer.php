<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\LdapUser;
use App\Entity\LocalUser;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class UserDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'APP_USER_DENORMALIZER_ALREADY_CALLED';

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED] = true;

        $targetClass = LocalUser::class;
        if (is_array($data)) {
            $isLdap = $data['isLdap'] ?? false;
            $userType = $data['type'] ?? null;
            if ($isLdap === true || $isLdap === 'true' || $userType === 'ldap') {
                $targetClass = LdapUser::class;
            }
        }

        return $this->denormalizer->denormalize($data, $targetClass, $format, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $type === User::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            User::class => false,
        ];
    }
}
