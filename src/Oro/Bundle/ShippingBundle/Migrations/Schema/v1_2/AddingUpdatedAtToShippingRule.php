<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddingUpdatedAtToShippingRule implements Migration, OrderedMigrationInterface
{
    public function getOrder()
    {
        return 1;
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_shipping_rule');
        $table->addColumn('created_at', 'datetime', ['notnull' => false]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->addIndex(['created_at'], 'idx_oro_shipping_rule_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_shipping_rule_updated_at', []);
    }
}
