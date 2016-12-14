<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShippingBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroShippingMethodsConfigsRuleTable($schema);
        $this->createOroShippingMethodsConfigsRuleDestinationPostalCodeTable($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createOroShippingMethodsConfigsRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_methods_conf_rule');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_shipping_methods_configs_rule_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_shipping_methods_configs_rule_updated_at', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * @param Schema $schema
     */
    private function createOroShippingMethodsConfigsRuleDestinationPostalCodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_methods_post_code');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('destination_id', 'integer', ['notnull' => true]);

        $table->setPrimaryKey(['id']);
    }
}
