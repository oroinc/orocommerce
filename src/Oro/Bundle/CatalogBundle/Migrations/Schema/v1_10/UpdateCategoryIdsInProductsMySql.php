<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Schema\Schema;

class UpdateCategoryIdsInProductsMySql extends UpdateCategoryIdsInProductsAbstract
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new self());
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuery()
    {
        return 'UPDATE %s p 
                INNER JOIN oro_category_to_product cp 
                SET p.%s = cp.category_id  
                WHERE p.id = cp.product_id'
        ;
    }
}
