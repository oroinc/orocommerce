<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BPricingBundleInstaller implements Installation, NoteExtensionAwareInterface
{
    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BPriceListTable($schema);
        $this->createOrob2BPriceListCurrencyTable($schema);
        $this->createOrob2BPriceListToAccGrTable($schema);
        $this->createOrob2BPriceListToAccountTable($schema);
        $this->createOrob2BPriceListToWebsiteTable($schema);
        $this->createOrob2BPriceProductTable($schema);
        $this->createOrob2BPriceListCombinedTable($schema);
        $this->createOrob2BPriceProductCombinedTable($schema);
        $this->createOrob2BPriceProductMinimalTable($schema);
        $this->createOrob2BPlistCurrCombinedTable($schema);
        $this->createOrob2BPriceListAccountFallbackTable($schema);
        $this->createOrob2BPriceListAccGroupFallbackTable($schema);
        $this->createOrob2BPriceListWebsiteFallbackTable($schema);
        $this->createOrob2BCmbPriceListToAccTable($schema);
        $this->createOrob2BCmbPriceListToAccGrTable($schema);
        $this->createOrob2BCmbPriceListToWsTable($schema);
        $this->createOrob2BCmbPlToPlTable($schema);
        $this->createOroB2BPriceListChangeTriggerTable($schema);
        $this->createOroB2BProductPriceChangeTriggerTable($schema);
        $this->createOroB2BPriceListScheduleTable($schema);
        $this->createOroB2BCplActivationRuleTable($schema);
        $this->createOroB2BPriceAttributeTable($schema);
        $this->createOroB2BPriceAttributeCurrencyTable($schema);
        $this->createOroB2BPriceAttributeProductPriceTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BPriceListCurrencyForeignKeys($schema);
        $this->addOrob2BPriceListToAccGrForeignKeys($schema);
        $this->addOrob2BPriceListToAccountForeignKeys($schema);
        $this->addOrob2BPriceListToWebsiteForeignKeys($schema);
        $this->addOrob2BPriceProductForeignKeys($schema);
        $this->addOrob2BPriceProductCombinedForeignKeys($schema);
        $this->addOrob2BPriceProductMinimalForeignKeys($schema);
        $this->addOrob2BPlistCurrCombinedForeignKeys($schema);
        $this->addOrob2BPriceListAccountFallbackForeignKeys($schema);
        $this->addOrob2BPriceListAccGroupFallbackForeignKeys($schema);
        $this->addOrob2BPriceListWebsiteFallbackForeignKeys($schema);
        $this->addOrob2BCmbPriceListToAccGrForeignKeys($schema);
        $this->addOrob2BCmbPriceListToWsForeignKeys($schema);
        $this->addOrob2BCmbPriceListToAccForeignKeys($schema);
        $this->addOrob2BCmbPlToPlForeignKeys($schema);
        $this->addOrob2BPriceListChangeTriggerForeignKeys($schema);
        $this->addOroB2BProductPriceChangeTriggerForeignKeys($schema);
        $this->addOroB2BPriceListScheduleForeignKeys($schema);
        $this->addOroB2BCplActivationRuleForeignKeys($schema);
        $this->addOroB2BPriceAttributeCurrencyForeignKeys($schema);
        $this->addOroB2BPriceAttributeProductPriceForeignKeys($schema);
    }

    /**
     * Create orob2b_price_list table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('is_default', 'boolean', []);
        $table->addColumn('active', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('contain_schedule', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);

        $this->noteExtension->addNoteAssociation($schema, 'orob2b_price_list');
    }

    /**
     * Create orob2b_price_list_currency table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_price_list_to_acc_group table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_acc_group');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['account_group_id', 'price_list_id', 'website_id']);
    }

    /**
     * Create orob2b_price_list_to_account table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToAccountTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_account');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['account_id', 'price_list_id', 'website_id']);
    }

    /**
     * Create orob2b_price_list_to_website table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_website');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['price_list_id', 'website_id']);
    }

    /**
     * Create orob2b_price_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['product_id', 'price_list_id', 'quantity', 'unit_code', 'currency'],
            'orob2b_pricing_price_list_uidx'
        );
    }

    /**
     * Create orob2b_price_list_combined table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListCombinedTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('is_enabled', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('is_prices_calculated', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_price_product_combined table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceProductCombinedTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_product_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('merge_allowed', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            [
                'product_id',
                'combined_price_list_id',
                'quantity',
                'unit_code',
                'currency'
            ],
            'orob2b_combined_price_uidx'
        );
    }

    /**
     * Create orob2b_price_product_minimal table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceProductMinimalTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_product_minimal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            [
                'product_id',
                'combined_price_list_id',
                'currency',
            ],
            'orob2b_minimal_price_uidx'
        );
    }

    /**
     * Create orob2b_plist_curr_combined table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPlistCurrCombinedTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_plist_curr_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_price_list_account_fallback table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListAccountFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_acc_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_id', 'website_id'], 'orob2b_price_list_acc_fb_unq');
    }

    /**
     * Create orob2b_price_list_acc_gr_fb table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListAccGroupFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_acc_gr_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_group_id', 'website_id'], 'orob2b_price_list_acc_gr_fb_unq');
    }

    /**
     * Create orob2b_price_list_website_fb table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListWebsiteFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_website_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id'], 'orob2b_price_list_website_fb_unq');
    }

    /**
     * Create orob2b_cmb_price_list_to_acc table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPriceListToAccTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_price_list_to_acc');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['account_id', 'website_id'], 'orob2b_cpl_to_acc_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cmb_plist_to_acc_gr table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_plist_to_acc_gr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['account_group_id', 'website_id'], 'orob2b_cpl_to_acc_gr_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cmb_price_list_to_ws table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPriceListToWsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_price_list_to_ws');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['website_id'], 'orob2b_cpl_to_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cmb_pl_to_pl table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPlToPlTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_pl_to_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('sort_order', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['combined_price_list_id', 'sort_order'], 'b2b_cmb_pl_to_pl_cmb_prod_sort_idx', []);
    }

    /**
     * Create orob2b_prod_price_ch_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductPriceChangeTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_prod_price_ch_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'orob2b_changed_product_price_list_unq');
    }

    /**
     * Create orob2b_price_list_schedule table
     *
     * @param Schema $schema
     */
    protected function createOroB2BPriceListScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deactivate_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['price_list_id'], 'IDX_C706756E5688DED7', []);
    }

    /**
     * Create orob2b_cpl_activation_rule table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCplActivationRuleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cpl_activation_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('activate_at', 'datetime', ['notnull' => false]);
        $table->addColumn('expire_at', 'datetime', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['combined_price_list_id'], 'IDX_E71CEADAF4E1C8D4', []);
        $table->addIndex(['full_combined_price_list_id'], 'IDX_E71CEADA579D9EF', []);
    }

    /**
     * Create orob2b_price_list_ch_trigger table
     *
     * @param Schema $schema
     */
    protected function createOroB2BPriceListChangeTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_ch_trigger');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('is_force', 'boolean', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPriceAttributeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_attribute');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPriceAttributeCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_attr_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPriceAttributeProductPriceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_attribute_price');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addUniqueIndex(
            ['product_id', 'price_attribute_id', 'quantity', 'unit_code', 'currency'],
            'orob2b_pricing_price_attribute_uidx'
        );
        $table->setPrimaryKey(['id']);
    }


    /**
     * Add orob2b_price_list_currency foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListCurrencyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_price_list_to_acc_group foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToAccGrForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_acc_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_price_list_to_account foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToAccountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_account');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_price_list_to_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_price_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_price_product_combined foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceProductCombinedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_product_combined');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_product_minimal foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceProductMinimalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_product_minimal');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_plist_curr_combined foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPlistCurrCombinedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_plist_curr_combined');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_list_account_fallback foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListAccountFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_acc_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_list_acc_gr_fb foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListAccGroupFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_acc_gr_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_list_website_fb foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListWebsiteFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_website_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_cmb_plist_to_acc_gr foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPriceListToAccGrForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_plist_to_acc_gr');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cmb_price_list_to_ws foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPriceListToWsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_price_list_to_ws');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_cmb_price_list_to_acc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPriceListToAccForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_price_list_to_acc');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_cmb_pl_to_pl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPlToPlForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_pl_to_pl');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_price_list_ch_trigger foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListChangeTriggerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_ch_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_prod_price_ch_trigger foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductPriceChangeTriggerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_prod_price_ch_trigger');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_price_list_schedule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BPriceListScheduleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_schedule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cpl_activation_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCplActivationRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cpl_activation_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPriceAttributeCurrencyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_attr_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_attribute'),
            ['price_attribute_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPriceAttributeProductPriceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_attribute_price');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_attribute'),
            ['price_attribute_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
