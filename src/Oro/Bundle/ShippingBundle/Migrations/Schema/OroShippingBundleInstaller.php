<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroShippingBundleInstaller implements Installation, NoteExtensionAwareInterface
{
    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroShippingFreightClassTable($schema);
        $this->createOroShippingLengthUnitTable($schema);
        $this->createOroShippingProductOptsTable($schema);
        $this->createOroShippingWeightUnitTable($schema);
        $this->createOroShippingRuleTable($schema);
        $this->createOroShippingRuleMthdConfigTable($schema);
        $this->createOroShippingRuleMthdTpCnfgTable($schema);
        $this->createOroShippingRuleDestinationTable($schema);

        /** Foreign keys generation **/
        $this->addOroShippingProductOptsForeignKeys($schema);
        $this->addOroShippingRuleMthdConfigForeignKeys($schema);
        $this->addOroShippingRuleMthdTpCnfgForeignKeys($schema);
        $this->addOroShippingRuleDestinationForeignKeys($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * Create oro_shipping_freight_class table
     *
     * @param Schema $schema
     */
    protected function createOroShippingFreightClassTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_freight_class');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_shipping_length_unit table
     *
     * @param Schema $schema
     */
    protected function createOroShippingLengthUnitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_length_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_shipping_product_opts table
     *
     * @param Schema $schema
     */
    protected function createOroShippingProductOptsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_product_opts');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('freight_class_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('dimensions_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('weight_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('weight_value', 'float', ['notnull' => false]);
        $table->addColumn('dimensions_length', 'float', ['notnull' => false]);
        $table->addColumn('dimensions_width', 'float', ['notnull' => false]);
        $table->addColumn('dimensions_height', 'float', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['product_id', 'product_unit_code'],
            'oro_shipping_product_opts_uidx'
        );
    }

    /**
     * Create oro_shipping_weight_unit table
     *
     * @param Schema $schema
     */
    protected function createOroShippingWeightUnitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_weight_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
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
        $table->addColumn('stop_processing', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['enabled', 'currency'], 'oro_shipping_rule_en_cur_idx', []);

        $this->noteExtension->addNoteAssociation($schema, 'oro_shipping_rule');
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
        $table->addColumn('enabled', 'boolean', ['default' => false]);
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
     * Add oro_shipping_product_opts foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShippingProductOptsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_product_opts');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_freight_class'),
            ['freight_class_code'],
            ['code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_length_unit'),
            ['dimensions_unit_code'],
            ['code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_weight_unit'),
            ['weight_unit_code'],
            ['code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
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
