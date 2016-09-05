<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShippingBundle implements Migration
{
    const ORO_SHIPPING_RULE_TABLE_NAME = 'oro_shipping_rule';
    const ORO_SHIPPING_DESTINATION_TABLE_NAME = 'oro_shipping_rl_destination';
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroShippingRuleTable($schema);
        $this->createOroShippingRuleDestinationTable($schema);
        $this->createOroShippingRuleConfigTable($schema);
        $this->createOroShipFlatRateRuleCnfTable($schema);

        /** Foreign keys generation **/
        $this->addOroShippingRuleDestinationForeignKeys($schema);
        $this->addOroShippingRuleConfigForeignKeys($schema);
        $this->addOroShipFlatRateRuleCnfForeignKeys($schema);
    }

    /**
     * Create oro_shipping_rule table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_SHIPPING_RULE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'text', ['notnull' => true]);
        $table->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('priority', 'integer', ['notnull' => true]);
        $table->addColumn('conditions', 'text', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('stop_processing', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['enabled', 'currency'], 'oro_shipping_rl_en_cur_idx', []);
    }

    /**
     * Create oro_shipping_rl_destination table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_SHIPPING_DESTINATION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('rule_id', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_shipping_rl_destination foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShippingRuleDestinationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_SHIPPING_DESTINATION_TABLE_NAME);
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
            $schema->getTable(self::ORO_SHIPPING_RULE_TABLE_NAME),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_shipping_rule_config table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_rule_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('entity_name', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_ship_flat_rate_rule_cnf table
     *
     * @param Schema $schema
     */
    protected function createOroShipFlatRateRuleCnfTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_flat_rate_rule_cnf');
        $table->addColumn('id', 'integer', []);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('handling_fee_value', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('processing_type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_shipping_rule_config foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShippingRuleConfigForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_ship_flat_rate_rule_cnf foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShipFlatRateRuleCnfForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_flat_rate_rule_cnf');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule_config'),
            ['id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
