<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Schema\Schema;

class UpdateCategoryIdsInProductsPqSql extends UpdateCategoryIdsInProductsAbstract
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
        return 'UPDATE %s p SET %s = cp.category_id FROM oro_category_to_product cp WHERE p.id = cp.product_id';
    }
}
