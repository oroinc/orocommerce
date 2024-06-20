<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Provides search term search index data for products.
 */
class SearchTermsIndexDataProvider
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    /**
     * @param array<int> $productsIds
     *
     * @return array<array{searchTermId: int, productCollectionSegmentId: int, productCollectionProductId: int}>
     */
    public function getSearchTermsDataForProducts(array $productsIds): array
    {
        if (!$productsIds) {
            return [];
        }

        $queryBuilder = $this->doctrine
            ->getRepository(SearchTerm::class)
            ->createQueryBuilder('search_term');

        $queryBuilder
            ->select('search_term.id as searchTermId')
            ->innerJoin(
                SegmentSnapshot::class,
                'segmentSnapshot',
                Join::WITH,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'IDENTITY(search_term.productCollectionSegment)',
                        'IDENTITY(segmentSnapshot.segment)'
                    ),
                    $queryBuilder->expr()->in('segmentSnapshot.integerEntityId', ':productCollectionProducts')
                )
            )
            ->addSelect('IDENTITY(segmentSnapshot.segment) as productCollectionSegmentId')
            ->addSelect('segmentSnapshot.integerEntityId as productCollectionProductId');

        $queryBuilder->setParameter('productCollectionProducts', $productsIds);

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
