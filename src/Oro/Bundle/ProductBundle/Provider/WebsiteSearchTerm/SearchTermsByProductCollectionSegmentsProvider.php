<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Provides {@see SearchTerm} entities referencing the specified {@see Segment} entities.
 */
class SearchTermsByProductCollectionSegmentsProvider
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    /**
     * @param array<int> $segmentIds
     *
     * @return iterable<SearchTerm>
     */
    public function getRelatedSearchTerms(array $segmentIds): iterable
    {
        $segmentIds = array_filter($segmentIds); // Remove empty entries
        if (!$segmentIds) {
            return [];
        }

        $queryBuilder = $this->doctrine
            ->getRepository(SearchTerm::class)
            ->createQueryBuilder('search_term');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('search_term.productCollectionSegment', ':segment_ids'))
            ->setParameter('segment_ids', $segmentIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->toIterable();
    }
}
