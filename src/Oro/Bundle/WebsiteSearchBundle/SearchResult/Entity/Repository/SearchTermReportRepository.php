<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;

/**
 * Repository for ORM Entity SearchTermReport.
 *
 * @method SearchTermReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearchTermReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearchTermReport[] findAll()
 * @method SearchTermReport[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchTermReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchTermReport::class);
    }
}
