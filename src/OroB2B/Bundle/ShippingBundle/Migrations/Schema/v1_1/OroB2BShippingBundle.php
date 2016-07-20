<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BShippingBundle implements Migration
{
    const ORO_B2B_SHIPPING_RULE_TABLE_NAME = 'orob2b_shipping_rule';
    const ORO_B2B_SHIPPING_DESTINATION_TABLE_NAME = 'orob2b_shipping_destination';
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BShippingRuleTable($schema);
        $this->createOrob2BShippingDestinationTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BShippingDestinationForeignKeys($schema);
    }

    /**
     * Create orob2b_shipping_rule table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingRuleTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_SHIPPING_RULE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('sort_order', 'integer', ['notnull' => true]);
        $table->addColumn('conditions', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Create orob2b_shipping_destination table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingDestinationTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_SHIPPING_DESTINATION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_rule_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_shipping_destination foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShippingDestinationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_SHIPPING_DESTINATION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_SHIPPING_RULE_TABLE_NAME),
            ['shipping_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
