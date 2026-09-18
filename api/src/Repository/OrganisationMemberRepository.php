<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OrganisationMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganisationMember>
 *
 * @method OrganisationMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganisationMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganisationMember[]    findAll()
 * @method OrganisationMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganisationMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganisationMember::class);
    }
}
