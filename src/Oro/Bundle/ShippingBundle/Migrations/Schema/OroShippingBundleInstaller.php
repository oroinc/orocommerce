<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroShippingBundleInstaller implements Installation, ActivityExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_7';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
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
    private function createOroShipMethodConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ship_method_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('method', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'IDX_838CE690744E0351');
    }

    /**
     * Create oro_ship_method_configs_rule table
     */
    private function createOroShipMethodConfigsRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ship_method_configs_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('currency', 'string', ['notnull' => true, 'length' => 3]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'IDX_1FA57D60744E0351');

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_ship_method_configs_rule');
    }

    /**
     * Create oro_ship_method_postal_code table
     */
    private function createOroShipMethodPostalCodeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ship_method_postal_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('destination_id', 'integer');
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['destination_id'], 'IDX_FD8EDF05816C6140');
    }

    /**
     * Create oro_ship_method_type_config table
     */
    private function createOroShipMethodTypeConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ship_method_type_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('method_config_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('options', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('enabled', 'boolean', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['method_config_id'], 'IDX_E04B78373A3C93A5');
    }

    /**
     * Create oro_shipping_freight_class table
     */
    private function createOroShippingFreightClassTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_shipping_freight_class');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_shipping_length_unit table
     */
    private function createOroShippingLengthUnitTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_shipping_length_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_shipping_product_opts table
     */
    private function createOroShippingProductOptsTable(Schema $schema): void
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
        $table->addUniqueIndex(['product_id', 'product_unit_code'], 'oro_shipping_product_opts_uidx');
        $table->addIndex(['product_id'], 'IDX_A7D10E3C4584665A');
        $table->addIndex(['product_unit_code'], 'IDX_A7D10E3C9573674F');
        $table->addIndex(['weight_unit_code'], 'IDX_A7D10E3CDBF67410');
        $table->addIndex(['dimensions_unit_code'], 'IDX_A7D10E3CBE541CA7');
        $table->addIndex(['freight_class_code'], 'IDX_A7D10E3C18783723');
    }

    /**
     * Create oro_shipping_rule_destination table
     */
    private function createOroShippingRuleDestinationTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_shipping_rule_destination');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['region_code'], 'IDX_BBAF16AAEB327AF');
        $table->addIndex(['country_code'], 'IDX_BBAF16AF026BB7C');
        $table->addIndex(['rule_id'], 'IDX_BBAF16A744E0351');
    }

    /**
     * Create oro_shipping_weight_unit table
     */
    private function createOroShippingWeightUnitTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_shipping_weight_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('conversion_rates', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Add oro_ship_method_config foreign keys.
     */
    private function addOroShipMethodConfigForeignKeys(Schema $schema): void
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
    private function addOroShipMethodConfigsRuleForeignKeys(Schema $schema): void
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
    private function addOroShipMethodPostalCodeForeignKeys(Schema $schema): void
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
    private function addOroShipMethodTypeConfigForeignKeys(Schema $schema): void
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
    private function addOroShippingProductOptsForeignKeys(Schema $schema): void
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
    private function addOroShippingRuleDestinationForeignKeys(Schema $schema): void
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
    private function createOroShipMtdsRuleWebsiteTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_ship_mtds_rule_website');
        $table->addColumn('oro_ship_mtds_cfgs_rl_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->setPrimaryKey(['oro_ship_mtds_cfgs_rl_id', 'website_id']);
        $table->addIndex(['oro_ship_mtds_cfgs_rl_id'], 'IDX_7EE052E912BB5ED3');
    }

    /**
     * Add oro_ship_mtds_rule_website foreign keys.
     */
    private function addOroShipMtdsRuleWebsiteForeignKeys(Schema $schema): void
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
}
