<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddSkuUppercaseColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $productTableName = 'oro_product';

        $table = $schema->getTable($productTableName);
        $table->addColumn('sku_uppercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['sku_uppercase'], 'idx_oro_product_sku_uppercase', []);

        // setup uppercase SKU column at upgrade time
        $queries->addPostQuery(
            new SqlMigrationQuery(
                "UPDATE $productTableName SET sku_uppercase = UPPER(sku)"
            )
        );
    }
}
