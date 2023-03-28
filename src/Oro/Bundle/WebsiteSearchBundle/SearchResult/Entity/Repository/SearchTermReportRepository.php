<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
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

    /**
     * Aggregates oro_website_search_term_report based on oro_website_search_result_history
     * created_at timestamp is converted to search_date with respect to TimeZone configured for Organization.
     */
    public function actualizeReport(OrganizationInterface $organization, \DateTimeZone $timeZone): void
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
    COUNT(*) FILTER ( WHERE results_count <> 0 ) as times_returned_results,
    COUNT(*) FILTER ( WHERE results_count = 0 ) as times_empty,
    DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE :timeZone) as search_date
FROM 
    oro_website_search_result_history
    WHERE organization_id = :organizationId %s
GROUP BY 
    normalized_search_term_hash, organization_id, business_unit_owner_id, 
    DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE :timeZone), lower(search_term)
ON CONFLICT ON CONSTRAINT website_search_term_report_term_unq 
DO UPDATE SET 
    times_searched = excluded.times_searched,
    times_returned_results = excluded.times_returned_results,
    times_empty = excluded.times_empty
SQL;

        $connection = $this->getEntityManager()->getConnection();
        $parameters = ['organizationId' => $organization->getId(), 'timeZone' => $timeZone->getName()];
        $types = ['organizationId' => Types::INTEGER, 'timeZone' => Types::STRING];

        $minDateStr = $connection->fetchOne(
            'SELECT MAX(search_date) FROM oro_website_search_term_report WHERE organization_id = ?',
            [$organization->getId()],
            [Types::INTEGER]
        );
        if ($minDateStr) {
            // Move data to report taking into account timezone set on organization level
            $reportSql = sprintf(
                $reportSql,
                "AND DATE(created_at AT TIME ZONE 'UTC' AT TIME ZONE :timeZone) >= :minCreatedAtDate"
            );
            $parameters['minCreatedAtDate'] = $minDateStr;
            $types['minCreatedAtDate'] = Types::STRING;
        } else {
            $reportSql = sprintf($reportSql, '');
        }

        $connection->executeStatement($reportSql, $parameters, $types);
    }
}
