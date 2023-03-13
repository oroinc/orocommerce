<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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

    public function actualizeReport(): void
    {
        $reportSql = <<<SQL
INSERT INTO oro_website_search_term_report (
    id, 
    business_unit_owner_id,
    organization_id,
    normalized_search_term_hash,
    search_term,
    times_searched, 
    times_returned_results,
    times_empty,
    search_date
)
SELECT
    uuid_generate_v4(),
    business_unit_owner_id,
    organization_id,
    normalized_search_term_hash,
    lower(search_term),
    COUNT(*) as times_searched,
    COUNT(*) FILTER ( WHERE results_count > 0 ) as times_returned_results,
    COUNT(*) FILTER ( WHERE results_count = 0 ) as times_empty,
    DATE(created_at) as search_date
FROM 
    oro_website_search_result_history
    %s
GROUP BY 
    normalized_search_term_hash, business_unit_owner_id, organization_id, DATE(created_at), lower(search_term)
ON CONFLICT ON CONSTRAINT website_search_term_report_term_unq 
DO UPDATE SET 
    times_searched = excluded.times_searched,
    times_returned_results = excluded.times_returned_results,
    times_empty = excluded.times_empty
SQL;

        $connection = $this->getEntityManager()->getConnection();
        $parameters = [];
        $types = [];

        $minDateStr = $connection->fetchOne('SELECT MAX(search_date) FROM oro_website_search_term_report');
        if ($minDateStr) {
            $reportSql = sprintf($reportSql, 'WHERE created_at BETWEEN ? AND ?');
            $minDate = new \DateTime($minDateStr . ' 00:00:00', new \DateTimeZone('UTC'));
            $parameters = [$minDate, new \DateTime(sprintf('now'), new \DateTimeZone('UTC'))];
            $types = [Types::DATETIME_MUTABLE, Types::DATETIME_MUTABLE];
        } else {
            $reportSql = sprintf($reportSql, '');
        }

        $connection->executeQuery($reportSql, $parameters, $types);
    }
}
