<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\Integration;
use App\Entity\Organisation;
use App\Entity\Project;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Logs create/update/delete operations on tracked entities.
 *
 * Logs are buffered during the unit of work and flushed in postFlush to avoid
 * nested flush errors ("Cannot call flush while flushing").
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class ActivityLogSubscriber
{
    private const TRACKED_ENTITY_TYPES = [
        Project::class => 'project',
        Organisation::class => 'organisation',
        Integration::class => 'integration',
        Server::class => 'server',
        User::class => 'user',
    ];

    /** @var list<ActivityLog> */
    private array $pendingLogs = [];

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->buffer($args->getObject(), 'created');
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->buffer($args->getObject(), 'updated');
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        // Capture identity/label before the entity is removed from the identity map.
        $this->buffer($args->getObject(), 'deleted');
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->pendingLogs === []) {
            return;
        }

        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $logs = $this->pendingLogs;
        $this->pendingLogs = [];

        foreach ($logs as $log) {
            $entityManager->persist($log);
        }

        // Triggers another postFlush; pendingLogs is empty so no recursion loop.
        $entityManager->flush();
    }

    private function buffer(object $entity, string $action): void
    {
        $entityType = $this->resolveEntityType($entity);
        if ($entityType === null) {
            return;
        }

        $id = $this->resolveEntityId($entity);
        if ($id === null) {
            return;
        }

        $log = (new ActivityLog())
            ->setEntityType($entityType)
            ->setEntityId($id)
            ->setEntityLabel($this->resolveEntityLabel($entity))
            ->setAction($action)
            ->setActor($this->resolveActor());

        $this->pendingLogs[] = $log;
    }

    private function resolveEntityType(object $entity): ?string
    {
        foreach (self::TRACKED_ENTITY_TYPES as $class => $type) {
            if ($entity instanceof $class) {
                return $type;
            }
        }

        return null;
    }

    private function resolveEntityId(object $entity): ?string
    {
        if (!method_exists($entity, 'getId')) {
            return null;
        }

        /** @var mixed $id */
        $id = $entity->getId();
        if ($id === null || (!is_int($id) && !is_string($id))) {
            return null;
        }

        return (string) $id;
    }

    private function resolveEntityLabel(object $entity): string
    {
        if ($entity instanceof User) {
            return $entity->getDisplayName()
                ?? $entity->getUsername()
                ?? $entity->getEmail()
                ?? 'User#'.($entity->getId() ?? '?');
        }

        if (method_exists($entity, 'getName')) {
            /** @var mixed $name */
            $name = $entity->getName();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        $type = $this->resolveEntityType($entity) ?? $entity::class;

        return $type.'#'.($this->resolveEntityId($entity) ?? '?');
    }

    private function resolveActor(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }
}
