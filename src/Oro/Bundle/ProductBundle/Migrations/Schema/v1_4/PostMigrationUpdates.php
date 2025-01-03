<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class PostMigrationUpdates implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 30;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateProductUnitPrecisionTable($schema);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function updateProductUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_unit_precision');
        $table->getColumn('sell')->setNotnull(true);
    }
}
