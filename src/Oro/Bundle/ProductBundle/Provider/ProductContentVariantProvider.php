<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Mix into contentVariant query information about simple products & variants, for Product variant.
 */
class ProductContentVariantProvider implements ContentVariantProviderInterface
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
            Product::class,
            'product',
            Join::WITH,
            'variant.product_page_product = product AND product IN (:products)'
        )
        ->setParameter('products', $entities)
        ->addSelect('product.id as productId');
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
        return $item['productId'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordSortOrder(array $item)
    {
        return null;
    }
}
