<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DeploymentServer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeploymentServer>
 */
class DeploymentServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeploymentServer::class);
    }
}
