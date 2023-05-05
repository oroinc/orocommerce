<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\ImportExport\Datagrid\CategoryFilterInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Category filter form PriceAttributeProductPrice entity
 */
class PriceAttributeProductPriceCategoryFilter implements CategoryFilterInterface
{
    private const FIELD_NAME = 'productCategory';

    public function getName(): string
    {
        return PriceAttributeProductPrice::class;
    }

    public function getFieldName(QueryBuilder $qb): string
    {
        return self::FIELD_NAME;
    }

    public function prepareQueryBuilder(QueryBuilder $qb): void
    {
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);

        $qb->join($rootAlias.'.product', 'product')
            ->join('product.category', self::FIELD_NAME);
    }
}
