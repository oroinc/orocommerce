<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

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
        $queryBuilder->leftJoin(
            Category::class,
            'category',
            Join::WITH,
            $queryBuilder->expr()->eq('variant.category_page_category', 'category')
        )
        ->leftJoin(
            Product::class,
            'categoryProduct',
            Join::WITH,
            'categoryProduct MEMBER OF category.products AND categoryProduct IN (:categoryProducts)'
        )
        ->setParameter('categoryProducts', $entities)
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
}
