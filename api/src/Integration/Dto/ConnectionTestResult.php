<?php

declare(strict_types=1);

namespace App\Integration\Dto;

final readonly class ConnectionTestResult
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public bool $success,
        public string $status,
        public string $message,
        public ?\DateTimeImmutable $testedAt = null,
        public array $details = []
    ) {
    }

    /**
     * @param array<string, mixed> $details
     */
    public static function success(string $message, array $details = []): self
    {
        return new self(
            success: true,
            status: 'healthy',
            message: $message,
            testedAt: new \DateTimeImmutable(),
            details: $details
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    public static function failure(string $message, array $details = []): self
    {
        return new self(
            success: false,
            status: 'error',
            message: $message,
            testedAt: new \DateTimeImmutable(),
            details: $details
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'statusMessage' => $this->message,
            'lastCheckedAt' => $this->testedAt?->format(\DateTimeInterface::ATOM),
            'details' => $this->details,
        ];
    }
}
