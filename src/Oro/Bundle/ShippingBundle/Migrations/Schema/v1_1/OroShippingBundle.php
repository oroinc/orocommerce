<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShippingBundle implements Migration
{
    const ORO_B2B_SHIPPING_RULE_TABLE_NAME = 'orob2b_shipping_rule';
    const ORO_B2B_SHIPPING_DESTINATION_TABLE_NAME = 'orob2b_shipping_rl_destination';
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BShippingRuleTable($schema);
        $this->createOrob2BShippingRuleDestinationTable($schema);
        $this->createOrob2BShippingRuleConfigTable($schema);
        $this->createOrob2BShipFlatRateRuleCnfTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BShippingRuleDestinationForeignKeys($schema);
        $this->addOrob2BShippingRuleConfigForeignKeys($schema);
        $this->addOrob2BShipFlatRateRuleCnfForeignKeys($schema);
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
        $table->addColumn('name', 'text', ['notnull' => true]);
        $table->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('priority', 'integer', ['notnull' => true]);
        $table->addColumn('conditions', 'text', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('stop_processing', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['enabled', 'currency'], 'orob2b_shipping_rl_en_cur_idx', []);
    }

    /**
     * Create orob2b_shipping_rl_destination table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_SHIPPING_DESTINATION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('rule_id', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_shipping_rl_destination foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShippingRuleDestinationForeignKeys(Schema $schema)
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
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_shipping_rule_config table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingRuleConfigTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shipping_rule_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('entity_name', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_ship_flat_rate_rule_cnf table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShipFlatRateRuleCnfTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_ship_flat_rate_rule_cnf');
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
     * Add orob2b_shipping_rule_config foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShippingRuleConfigForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shipping_rule_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_shipping_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_ship_flat_rate_rule_cnf foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShipFlatRateRuleCnfForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_ship_flat_rate_rule_cnf');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_shipping_rule_config'),
            ['id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
