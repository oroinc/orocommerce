<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_1;

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
        /** Tables generation **/
        $this->createOroShippingRuleTable($schema);
        $this->createOroShippingRuleMthdConfigTable($schema);
        $this->createOroShippingRuleMthdTpCnfgTable($schema);
        $this->createOroShippingRuleDestinationTable($schema);

        /** Foreign keys generation **/
        $this->addOroShippingRuleMthdConfigForeignKeys($schema);
        $this->addOroShippingRuleMthdTpCnfgForeignKeys($schema);
        $this->addOroShippingRuleDestinationForeignKeys($schema);
    }

    /**
     * Create oro_shipping_rule table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'text', []);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('conditions', 'text', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('stop_processing', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['enabled', 'currency'], 'oro_shipping_rule_en_cur_idx', []);
    }

    /**
     * Create oro_shipping_rule_mthd_config table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleMthdConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_rule_mthd_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', []);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_shipping_rule_mthd_tp_cnfg table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleMthdTpCnfgTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_rule_mthd_tp_cnfg');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('method_config_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_shipping_rule_destination table
     *
     * @param Schema $schema
     */
    protected function createOroShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_rule_destination');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', []);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_shipping_rule_mthd_config foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShippingRuleMthdConfigForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_mthd_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_shipping_rule_mthd_tp_cnfg foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShippingRuleMthdTpCnfgForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_mthd_tp_cnfg');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule_mthd_config'),
            ['method_config_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_shipping_rule_destination foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShippingRuleDestinationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_destination');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }
}
