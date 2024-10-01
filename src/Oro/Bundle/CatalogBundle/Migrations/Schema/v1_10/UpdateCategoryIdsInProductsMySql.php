<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

class UpdateCategoryIdsInProductsMySql extends UpdateCategoryIdsInProductsAbstract
{
    #[\Override]
    protected function getQuery()
    {
        return 'UPDATE %s p 
                INNER JOIN oro_category_to_product cp 
                SET p.%s = cp.category_id  
                WHERE p.id = cp.product_id'
        ;
    }
}
