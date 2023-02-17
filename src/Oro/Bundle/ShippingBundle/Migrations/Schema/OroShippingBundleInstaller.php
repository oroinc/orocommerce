<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroShippingBundleInstaller implements Installation, ActivityExtensionAwareInterface
{
    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_7';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroShipMethodConfigTable($schema);
        $this->createOroShipMethodConfigsRuleTable($schema);
        $this->createOroShipMethodPostalCodeTable($schema);
        $this->createOroShipMethodTypeConfigTable($schema);
        $this->createOroShippingFreightClassTable($schema);
        $this->createOroShippingLengthUnitTable($schema);
        $this->createOroShippingProductOptsTable($schema);
        $this->createOroShippingRuleDestinationTable($schema);
        $this->createOroShippingWeightUnitTable($schema);
        $this->createOroShipMtdsRuleWebsiteTable($schema);

        /** Foreign keys generation **/
        $this->addOroShipMethodConfigForeignKeys($schema);
        $this->addOroShipMethodConfigsRuleForeignKeys($schema);
        $this->addOroShipMethodPostalCodeForeignKeys($schema);
        $this->addOroShipMethodTypeConfigForeignKeys($schema);
        $this->addOroShippingProductOptsForeignKeys($schema);
        $this->addOroShippingRuleDestinationForeignKeys($schema);
        $this->addOroShipMtdsRuleWebsiteForeignKeys($schema);
    }

    /**
     * Create oro_ship_method_config table
     */
    protected function createOroShipMethodConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_method_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'IDX_838CE690744E0351', []);
    }

    /**
     * Create oro_ship_method_configs_rule table
     */
    protected function createOroShipMethodConfigsRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_method_configs_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('currency', 'string', ['notnull' => true, 'length' => 3]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'IDX_1FA57D60744E0351', []);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_ship_method_configs_rule');
    }

    /**
     * Create oro_ship_method_postal_code table
     */
    protected function createOroShipMethodPostalCodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_method_postal_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('destination_id', 'integer', []);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['destination_id'], 'IDX_FD8EDF05816C6140', []);
    }

    /**
     * Create oro_ship_method_type_config table
     */
    protected function createOroShipMethodTypeConfigTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_method_type_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('method_config_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('enabled', 'boolean', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['method_config_id'], 'IDX_E04B78373A3C93A5', []);
    }

    /**
     * Create oro_shipping_freight_class table
     */
    protected function createOroShippingFreightClassTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_freight_class');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_shipping_length_unit table
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
     */
    protected function createOroShippingProductOptsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_product_opts');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('freight_class_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('dimensions_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('weight_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('weight_value', 'float', ['notnull' => false]);
        $table->addColumn('dimensions_length', 'float', ['notnull' => false]);
        $table->addColumn('dimensions_width', 'float', ['notnull' => false]);
        $table->addColumn('dimensions_height', 'float', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'product_unit_code'], 'oro_shipping_product_opts_uidx');
        $table->addIndex(['product_id'], 'IDX_A7D10E3C4584665A', []);
        $table->addIndex(['product_unit_code'], 'IDX_A7D10E3C9573674F', []);
        $table->addIndex(['weight_unit_code'], 'IDX_A7D10E3CDBF67410', []);
        $table->addIndex(['dimensions_unit_code'], 'IDX_A7D10E3CBE541CA7', []);
        $table->addIndex(['freight_class_code'], 'IDX_A7D10E3C18783723', []);
    }

    /**
     * Create oro_shipping_rule_destination table
     */
    protected function createOroShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_rule_destination');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['region_code'], 'IDX_BBAF16AAEB327AF', []);
        $table->addIndex(['country_code'], 'IDX_BBAF16AF026BB7C', []);
        $table->addIndex(['rule_id'], 'IDX_BBAF16A744E0351', []);
    }

    /**
     * Create oro_shipping_weight_unit table
     */
    protected function createOroShippingWeightUnitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shipping_weight_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Add oro_ship_method_config foreign keys.
     */
    protected function addOroShipMethodConfigForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_method_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_ship_method_configs_rule foreign keys.
     */
    protected function addOroShipMethodConfigsRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_method_configs_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_ship_method_postal_code foreign keys.
     */
    protected function addOroShipMethodPostalCodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_method_postal_code');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule_destination'),
            ['destination_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_ship_method_type_config foreign keys.
     */
    protected function addOroShipMethodTypeConfigForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_method_type_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_config'),
            ['method_config_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_shipping_product_opts foreign keys.
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
     * Add oro_shipping_rule_destination foreign keys.
     */
    protected function addOroShippingRuleDestinationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_destination');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Create oro_ship_mtds_rule_website table
     */
    protected function createOroShipMtdsRuleWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_mtds_rule_website');
        $table->addColumn('oro_ship_mtds_cfgs_rl_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['oro_ship_mtds_cfgs_rl_id', 'website_id']);
        $table->addIndex(['oro_ship_mtds_cfgs_rl_id'], 'IDX_7EE052E912BB5ED3', []);
    }

    /**
     * Add oro_ship_mtds_rule_website foreign keys.
     */
    protected function addOroShipMtdsRuleWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_mtds_rule_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['oro_ship_mtds_cfgs_rl_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }
}
