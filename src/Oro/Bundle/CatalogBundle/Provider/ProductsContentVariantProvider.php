<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Modify contentVariant query and add information about related products for Category page variants.
 */
class ProductsContentVariantProvider implements ContentVariantProviderInterface
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
        $categoryIdentity = 'IDENTITY(variant.category_page_category)';
        $expr = $queryBuilder->expr();
        $queryBuilder->leftJoin(
            Category::class,
            'category',
            Join::WITH,
            $expr->orX(
                $expr->eq(
                    'category.id',
                    $categoryIdentity
                ),
                $expr->like(
                    'category.materializedPath',
                    $expr->concat($categoryIdentity, ':rightDelimiter')
                ),
                $expr->like(
                    'category.materializedPath',
                    $expr->concat(
                        ':leftDelimiter',
                        $expr->concat($categoryIdentity, ':rightDelimiter')
                    )
                )
            )
        )
        ->leftJoin(
            Product::class,
            'categoryProduct',
            Join::WITH,
            $expr->andX(
                $expr->eq('categoryProduct.category', 'category.id'),
                $expr->in('categoryProduct', ':categoryProducts')
            )
        )
        ->setParameter('categoryProducts', $entities)
        ->setParameter('rightDelimiter', '\_%')
        ->setParameter('leftDelimiter', '%\_')
        ->addSelect('categoryProduct.id as categoryProductId');
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
        return $item['categoryProductId'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordSortOrder(array $item)
    {
        return null;
    }
}
