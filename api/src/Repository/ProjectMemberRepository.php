<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectMember>
 *
 * @method ProjectMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectMember[]    findAll()
 * @method ProjectMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectMember::class);
    }
}
