<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

class UpdateCategoryIdsInProductsPqSql extends UpdateCategoryIdsInProductsAbstract
{
    /**
     * {@inheritDoc}
     */
    protected function getQuery()
    {
        return 'UPDATE %s p SET %s = cp.category_id FROM oro_category_to_product cp WHERE p.id = cp.product_id';
    }
}
