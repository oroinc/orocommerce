<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddingUpdatedAtToShippingRule implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_shipping_rule');
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addIndex(['created_at'], 'idx_oro_shipping_rule_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_shipping_rule_updated_at', []);
    }
}
