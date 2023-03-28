<?php

namespace Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;

/**
 * Repository for ORM Entity SearchResultHistory.
 *
 * @method SearchResultHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearchResultHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearchResultHistory[] findAll()
 * @method SearchResultHistory[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchResultHistoryRepository extends ServiceEntityRepository
{
    protected const KEEP_DAYS = 30;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchResultHistory::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function upsertSearchHistoryRecord(
        string $searchTerm,
        string $resultType,
        int $resultsCount,
        string $searchTermHash,
        int $businessUnitId,
        int $websiteId = null,
        ?string $searchSessionId = null,
        ?int $localizationId = null,
        ?int $customerId = null,
        ?int $customerUserId = null,
        ?int $customerVisitorId = null,
        ?int $organizationId = null,
    ): void {
        $query = "
            INSERT INTO oro_website_search_result_history (
                id,
                website_id,
                localization_id,
                customer_id, 
                customer_user_id,
                customer_visitor_id,
                business_unit_owner_id,
                organization_id, 
                normalized_search_term_hash, 
                result_type,
                results_count,
                search_session,
                search_term,
                created_at
            ) VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            ON CONFLICT ON CONSTRAINT website_search_result_history_search_session_unq DO UPDATE SET
               results_count = excluded.results_count,
               result_type = excluded.result_type,
               search_term = excluded.search_term,
               normalized_search_term_hash = excluded.normalized_search_term_hash,
               created_at = excluded.created_at";

        $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            [
                $websiteId,
                $localizationId,
                $customerId,
                $customerUserId,
                $customerVisitorId,
                $businessUnitId,
                $organizationId,
                $searchTermHash,
                $resultType,
                $resultsCount,
                $searchSessionId,
                $searchTerm,
                new \DateTime('now', new \DateTimeZone('UTC'))
            ],
            [
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
                Types::INTEGER,
                Types::STRING,
                Types::STRING,
                Types::INTEGER,
                Types::STRING,
                Types::STRING,
                Types::DATETIME_MUTABLE
            ]
        );
    }

    public function removeOldRecords(int $keepDays = self::KEEP_DAYS): void
    {
        $this->getEntityManager()->getConnection()->executeQuery(
            'DELETE FROM oro_website_search_result_history WHERE DATE(created_at) < ?',
            [new \DateTime(sprintf('-%d days', $keepDays), new \DateTimeZone('UTC'))],
            [Types::DATE_MUTABLE]
        );
    }

    public function getOrganizationsByHistory(): iterable
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('IDENTITY(e.organization) as organizationId')
            ->distinct(true);

        foreach ($qb->getQuery()->getArrayResult() as $row) {
            yield $this->getEntityManager()->getReference(Organization::class, $row['organizationId']);
        }
    }
}
