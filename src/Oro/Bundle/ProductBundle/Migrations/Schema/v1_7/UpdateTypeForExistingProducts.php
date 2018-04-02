<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateTypeForExistingProducts implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $productTableName = AddColumnTypeToProduct::PRODUCT_TABLE_NAME;

        // Add type 'simple' to all existing products
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                "UPDATE $productTableName SET type = :typeValue WHERE type IS NULL",
                ['typeValue' => 'simple']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
