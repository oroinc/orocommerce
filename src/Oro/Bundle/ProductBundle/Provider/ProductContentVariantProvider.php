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
    #[\Override]
    public function isSupportedClass($className)
    {
        return $className === Product::class;
    }

    #[\Override]
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

    #[\Override]
    public function getValues(ContentNodeInterface $node)
    {
        return [];
    }

    #[\Override]
    public function getLocalizedValues(ContentNodeInterface $node)
    {
        return [];
    }

    #[\Override]
    public function getRecordId(array $item)
    {
        return $item['productId'];
    }

    #[\Override]
    public function getRecordSortOrder(array $item)
    {
        return null;
    }
}
