<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Mix into contentVariant query information about related products & collection sort orders,
 * for ProductCollectionSegment variant.
 * Fetching for this data from snapshot of related segment.
 */
class ProductCollectionContentVariantProvider implements ContentVariantProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupportedClass($className)
    {
        return $className === Product::class;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyNodeQueryBuilderByEntities(QueryBuilder $queryBuilder, $entityClass, array $entities)
    {
        $queryBuilder->leftJoin(
            SegmentSnapshot::class,
            'segmentSnapshot',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq('variant.product_collection_segment', 'segmentSnapshot.segment'),
                $queryBuilder->expr()->in('segmentSnapshot.integerEntityId', ':productCollectionProducts')
            )
        )
        ->addSelect('segmentSnapshot.integerEntityId as productCollectionProductId');

        $queryBuilder->leftJoin(
            CollectionSortOrder::class,
            'collectionSortOrder',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq('variant.product_collection_segment', 'collectionSortOrder.segment'),
                $queryBuilder->expr()->eq('segmentSnapshot.integerEntityId', 'collectionSortOrder.product')
            )
        )
        ->addSelect('collectionSortOrder.sortOrder as sortOrderValue');

        $queryBuilder->setParameter('productCollectionProducts', $entities);
    }

    /**
     * {@inheritdoc}
     */
    public function getValues(ContentNodeInterface $node)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizedValues(ContentNodeInterface $node)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordId(array $item)
    {
        return $item['productCollectionProductId'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordSortOrder(array $item)
    {
        if (null === $item['productCollectionProductId']) {
            return null;
        }

        return $item['sortOrderValue'];
    }
}
