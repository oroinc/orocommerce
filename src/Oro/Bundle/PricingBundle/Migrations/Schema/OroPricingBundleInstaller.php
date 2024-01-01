<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroPricingBundleInstaller implements Installation, ActivityExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_26';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroPriceListTable($schema);
        $this->createOroPriceListCurrencyTable($schema);
        $this->createOroPriceListToCustomerGroupTable($schema);
        $this->createOroPriceListToCustomerTable($schema);
        $this->createOroPriceListToWebsiteTable($schema);
        $this->createOroPriceProductTable($schema);
        $this->createOroPriceListCombinedTable($schema);
        $this->createOroPriceProductCombinedTable($schema);
        $this->createOroPlistCurrCombinedTable($schema);
        $this->createOroPriceListAccountFallbackTable($schema);
        $this->createOroPriceListAccGroupFallbackTable($schema);
        $this->createOroPriceListWebsiteFallbackTable($schema);
        $this->createOroCmbPriceListToAccTable($schema);
        $this->createOroCmbPriceListToAccGrTable($schema);
        $this->createOroCmbPriceListToWsTable($schema);
        $this->createOroCmbPlToPlTable($schema);
        $this->createOroPriceListScheduleTable($schema);
        $this->createOroCplActivationRuleTable($schema);
        $this->createOroPriceAttributeTable($schema);
        $this->createOroPriceAttributeCurrencyTable($schema);
        $this->createOroPriceAttributeProductPriceTable($schema);
        $this->createOroriceListToProductTable($schema);
        $this->createOroPriceRuleTable($schema);
        $this->createOroPriceRuleLexemeTable($schema);
        $this->createOroPriceListCombinedBuildActivityTable($schema);
        $this->createOroPriceListCombinedGCTable($schema);

        /** Foreign keys generation **/
        $this->addOroPriceListForeignKeys($schema);
        $this->addOroPriceListCurrencyForeignKeys($schema);
        $this->addOroPriceListToAccGrForeignKeys($schema);
        $this->addOroPriceListToAccountForeignKeys($schema);
        $this->addOroPriceListToWebsiteForeignKeys($schema);
        $this->addOroPriceProductForeignKeys($schema);
        $this->addOroPriceProductCombinedForeignKeys($schema);
        $this->addOroPlistCurrCombinedForeignKeys($schema);
        $this->addOroPriceListAccountFallbackForeignKeys($schema);
        $this->addOroPriceListAccGroupFallbackForeignKeys($schema);
        $this->addOroPriceListWebsiteFallbackForeignKeys($schema);
        $this->addOroCmbPriceListToAccGrForeignKeys($schema);
        $this->addOroCmbPriceListToWsForeignKeys($schema);
        $this->addOroCmbPriceListToAccForeignKeys($schema);
        $this->addOroCmbPlToPlForeignKeys($schema);
        $this->addOroPriceListScheduleForeignKeys($schema);
        $this->addOroCplActivationRuleForeignKeys($schema);
        $this->addOroPriceAttributeCurrencyForeignKeys($schema);
        $this->addOroPriceAttributeProductPriceForeignKeys($schema);
        $this->addOroPriceAttributeOrganizationForeignKeys($schema);
        $this->addOroriceListToProductForeignKeys($schema);
        $this->addOroPriceRuleForeignKeys($schema);
        $this->addOroPriceRuleLexemeForeignKeys($schema);
        $this->addOroPriceListCombinedBuildActivityForeignKeys($schema);
        $this->addOroPriceListCombinedGCForeignKeys($schema);
    }

    /**
     * Create oro_price_list table
     */
    private function createOroPriceListTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('active', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('actual', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('product_assignment_rule', 'text', ['notnull' => false]);
        $table->addColumn('contain_schedule', 'boolean');
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_price_list');
    }

    /**
     * Create oro_price_list_currency table
     */
    private function createOroPriceListCurrencyTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_list_to_cus_group table
     */
    private function createOroPriceListToCustomerGroupTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_to_cus_group');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('customer_group_id', 'integer');
        $table->addColumn('sort_order', 'integer');
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_group_id', 'price_list_id', 'website_id']);
    }

    /**
     * Create oro_price_list_to_customer table
     */
    private function createOroPriceListToCustomerTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_to_customer');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('customer_id', 'integer');
        $table->addColumn('sort_order', 'integer');
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_id', 'price_list_id', 'website_id']);
    }

    /**
     * Create oro_price_list_to_website table
     */
    private function createOroPriceListToWebsiteTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_to_website');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('sort_order', 'integer');
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['price_list_id', 'website_id']);
    }

    /**
     * Create oro_price_product table
     */
    private function createOroPriceProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_product');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('price_rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('version', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['product_id', 'price_list_id', 'quantity', 'unit_code', 'currency'],
            'oro_pricing_price_list_uidx'
        );
        $table->addIndex(['price_list_id', 'version', 'product_id'], 'oro_price_version_idx');
    }

    /**
     * Create oro_price_list_combined table
     */
    private function createOroPriceListCombinedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('is_enabled', 'boolean');
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('is_prices_calculated', 'boolean');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_product_combined table
     */
    private function createOroPriceProductCombinedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_product_combined');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('origin_price_id', 'guid', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('combined_price_list_id', 'integer');
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('merge_allowed', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addIndex(
            ['combined_price_list_id', 'product_id', 'currency', 'unit_code', 'quantity'],
            'oro_combined_price_idx'
        );
        $table->addIndex(['combined_price_list_id', 'product_id', 'merge_allowed'], 'oro_cmb_price_mrg_idx');
        $table->addIndex(['product_id', 'currency'], 'oro_cmb_price_product_currency_idx');
    }

    /**
     * Create oro_plist_curr_combined table
     */
    private function createOroPlistCurrCombinedTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_plist_curr_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_list_customer_fallback table
     */
    private function createOroPriceListAccountFallbackTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_cus_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('fallback', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_id', 'website_id'], 'oro_price_list_cus_fb_unq');
    }

    /**
     * Create oro_price_list_cus_gr_fb table
     */
    private function createOroPriceListAccGroupFallbackTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_cus_gr_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_group_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('fallback', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_group_id', 'website_id'], 'oro_price_list_cus_gr_fb_unq');
    }

    /**
     * Create oro_price_list_website_fb table
     */
    private function createOroPriceListWebsiteFallbackTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_website_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer');
        $table->addColumn('fallback', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id'], 'oro_price_list_website_fb_unq');
    }

    /**
     * Create oro_cmb_price_list_to_cus table
     */
    private function createOroCmbPriceListToAccTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cmb_price_list_to_cus');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('version', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_id', 'website_id'], 'oro_cpl_to_cus_ws_unq');
    }

    /**
     * Create oro_cmb_plist_to_cus_gr table
     */
    private function createOroCmbPriceListToAccGrTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cmb_plist_to_cus_gr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_group_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('version', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_group_id', 'website_id'], 'oro_cpl_to_cus_gr_ws_unq');
    }

    /**
     * Create oro_cmb_price_list_to_ws table
     */
    private function createOroCmbPriceListToWsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cmb_price_list_to_ws');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('version', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id'], 'oro_cpl_to_ws_unq');
    }

    /**
     * Create oro_cmb_pl_to_pl table
     */
    private function createOroCmbPlToPlTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cmb_pl_to_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('combined_price_list_id', 'integer');
        $table->addColumn('sort_order', 'integer');
        $table->addColumn('merge_allowed', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['combined_price_list_id', 'sort_order'], 'cmb_pl_to_pl_cmb_prod_sort_idx');
    }

    /**
     * Create oro_price_list_schedule table
     */
    private function createOroPriceListScheduleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deactivate_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['price_list_id'], 'IDX_C706756E5688DED7');
    }

    /**
     * Create oro_cpl_activation_rule table
     */
    private function createOroCplActivationRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cpl_activation_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('activate_at', 'datetime', ['notnull' => false]);
        $table->addColumn('expire_at', 'datetime', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['combined_price_list_id'], 'IDX_E71CEADAF4E1C8D4');
        $table->addIndex(['full_combined_price_list_id'], 'IDX_E71CEADA579D9EF');
    }

    private function createOroPriceAttributeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_attribute_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('is_enabled_in_export', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    private function createOroPriceAttributeCurrencyTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_attr_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_pl_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    private function createOroPriceAttributeProductPriceTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_attribute_price');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_pl_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('value', 'money');
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['product_id', 'price_attribute_pl_id', 'quantity', 'unit_code', 'currency'],
            'oro_pricing_price_attribute_uidx'
        );
    }

    /**
     * Create oro_price_list_to_product table
     */
    private function createOroriceListToProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_to_product');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('is_manual', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'oro_price_list_to_product_uidx');
    }

    /**
     * Create oro_price_rule table
     */
    private function createOroPriceRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('rule_condition', 'text', ['notnull' => false]);
        $table->addColumn('rule', 'text', ['notnull' => true]);
        $table->addColumn('priority', 'integer');
        $table->addColumn('quantity_expression', 'text', ['notnull' => false]);
        $table->addColumn('currency_expression', 'text', ['notnull' => false]);
        $table->addColumn('product_unit_expression', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_rule_lexeme table
     */
    private function createOroPriceRuleLexemeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_rule_lexeme');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('price_list_id', 'integer');
        $table->addColumn('class_name', 'string', ['length' => 255]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('relation_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function createOroPriceListCombinedBuildActivityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_combined_build_activity');
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_job_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['parent_job_id'], 'oro_cpl_build_activity_job_idx');
    }

    /**
     * Create `oro_combined_price_gc' table.
     *
     * This table stores CPL removal request by GC. Records are removed from this table only when actual CPL removal is
     * performed. Presence of CPL in the table does not mean that it will be actually removed, the request actuality is
     * checked and the moment of actual removal.
     */
    private function createOroPriceListCombinedGCTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_price_list_combined_gc');
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('requested_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['combined_price_list_id'], 'oro_cpl_gc_unq_idx');
    }

    private function addOroPriceListCombinedGCForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_combined_gc');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_list foreign keys.
     */
    private function addOroPriceListForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_price_list_currency foreign keys.
     */
    private function addOroPriceListCurrencyForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_list_to_cus_group foreign keys.
     */
    private function addOroPriceListToAccGrForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_to_cus_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_list_to_customer foreign keys.
     */
    private function addOroPriceListToAccountForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_to_customer');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_list_to_website foreign keys.
     */
    private function addOroPriceListToWebsiteForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_to_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_product foreign keys.
     */
    private function addOroPriceProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_rule'),
            ['price_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_product_combined foreign keys.
     */
    private function addOroPriceProductCombinedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_product_combined');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_plist_curr_combined foreign keys.
     */
    private function addOroPlistCurrCombinedForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_plist_curr_combined');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_price_list_customer_fallback foreign keys.
     */
    private function addOroPriceListAccountFallbackForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_cus_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_price_list_cus_gr_fb foreign keys.
     */
    private function addOroPriceListAccGroupFallbackForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_cus_gr_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_price_list_website_fb foreign keys.
     */
    private function addOroPriceListWebsiteFallbackForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_website_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cmb_plist_to_cus_gr foreign keys.
     */
    private function addOroCmbPriceListToAccGrForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cmb_plist_to_cus_gr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customer_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cmb_price_list_to_ws foreign keys.
     */
    private function addOroCmbPriceListToWsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cmb_price_list_to_ws');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cmb_price_list_to_cus foreign keys.
     */
    private function addOroCmbPriceListToAccForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cmb_price_list_to_cus');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cmb_pl_to_pl foreign keys.
     */
    private function addOroCmbPlToPlForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cmb_pl_to_pl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_price_list_schedule foreign keys.
     */
    private function addOroPriceListScheduleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_schedule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cpl_activation_rule foreign keys.
     */
    private function addOroCplActivationRuleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cpl_activation_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroPriceAttributeCurrencyForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_attr_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_attribute_pl'),
            ['price_attribute_pl_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOroPriceAttributeProductPriceForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_attribute_price');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_attribute_pl'),
            ['price_attribute_pl_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_price_list_to_product foreign keys.
     */
    private function addOroriceListToProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_rule foreign keys.
     */
    private function addOroPriceRuleForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_price_rule_lexeme foreign keys.
     */
    private function addOroPriceRuleLexemeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_rule_lexeme');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_rule'),
            ['price_rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroPriceAttributeOrganizationForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_attribute_pl');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function addOroPriceListCombinedBuildActivityForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_price_list_combined_build_activity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
