<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Mix into contentVariant query information about related products, for ProductCollectionSegment variant.
 * Fetching for this products from snapshot of related segment.
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
        ->setParameter('productCollectionProducts', $entities)
        ->addSelect('segmentSnapshot.integerEntityId as productCollectionProductId');
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
}
