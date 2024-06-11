<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Provides {@see Segment} entities referenced by {@see SearchTerm} entities.
 */
class SearchTermProductCollectionSegmentsProvider
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public function getSearchTermProductCollectionSegments(?int $websiteId = null): array
    {
        $searchTermRepo = $this->doctrine->getRepository(Segment::class);
        $queryBuilder = $searchTermRepo->createQueryBuilder('segment');
        $queryBuilder
            ->select('DISTINCT segment')
            ->innerJoin(
                SearchTerm::class,
                'search_term',
                Join::WITH,
                $queryBuilder->expr()->eq('search_term.productCollectionSegment', 'segment')
            );

        if ($websiteId !== null) {
            $queryBuilder
                ->innerJoin(
                    'search_term.scopes',
                    'scope',
                    Join::WITH,
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('IDENTITY(scope.website)', ':website_id'),
                        $queryBuilder->expr()->isNull('IDENTITY(scope.website)')
                    )
                )
                ->setParameter('website_id', $websiteId, Types::INTEGER);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
