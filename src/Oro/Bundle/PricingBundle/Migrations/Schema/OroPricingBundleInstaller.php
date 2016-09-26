<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroPricingBundleInstaller implements Installation, NoteExtensionAwareInterface
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
        return 'v1_6';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroPriceListTable($schema);
        $this->createOroPriceListCurrencyTable($schema);
        $this->createOroPriceListToAccGrTable($schema);
        $this->createOroPriceListToAccountTable($schema);
        $this->createOroPriceListToWebsiteTable($schema);
        $this->createOroPriceProductTable($schema);
        $this->createOroPriceListCombinedTable($schema);
        $this->createOroPriceProductCombinedTable($schema);
        $this->createOroPriceProductMinimalTable($schema);
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
        $this->createOroNotificationMessageTable($schema);

        /** Foreign keys generation **/
        $this->addOroPriceListCurrencyForeignKeys($schema);
        $this->addOroPriceListToAccGrForeignKeys($schema);
        $this->addOroPriceListToAccountForeignKeys($schema);
        $this->addOroPriceListToWebsiteForeignKeys($schema);
        $this->addOroPriceProductForeignKeys($schema);
        $this->addOroPriceProductCombinedForeignKeys($schema);
        $this->addOroPriceProductMinimalForeignKeys($schema);
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
        $this->addOroriceListToProductForeignKeys($schema);
        $this->addOroPriceRuleForeignKeys($schema);
        $this->addOroPriceRuleLexemeForeignKeys($schema);
    }

    /**
     * Create oro_price_list table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('is_default', 'boolean', []);
        $table->addColumn('active', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('actual', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('product_assignment_rule', 'text', ['notnull' => false]);
        $table->addColumn('contain_schedule', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);

        $this->noteExtension->addNoteAssociation($schema, 'oro_price_list');
    }

    /**
     * Create oro_price_list_currency table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_list_to_acc_group table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_to_acc_group');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['account_group_id', 'price_list_id', 'website_id']);
    }

    /**
     * Create oro_price_list_to_account table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListToAccountTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_to_account');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['account_id', 'price_list_id', 'website_id']);
    }

    /**
     * Create oro_price_list_to_website table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListToWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_to_website');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['price_list_id', 'website_id']);
    }

    /**
     * Create oro_price_product table
     *
     * @param Schema $schema
     */
    protected function createOroPriceProductTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_rule_id', 'integer', ['notnull' => false]);
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
            'oro_pricing_price_list_uidx'
        );
    }

    /**
     * Create oro_price_list_combined table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListCombinedTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('is_enabled', 'boolean', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('is_prices_calculated', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_product_combined table
     *
     * @param Schema $schema
     */
    protected function createOroPriceProductCombinedTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_product_combined');
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
            'oro_combined_price_uidx'
        );
    }

    /**
     * Create oro_price_product_minimal table
     *
     * @param Schema $schema
     */
    protected function createOroPriceProductMinimalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_product_minimal');
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
            'oro_minimal_price_uidx'
        );
    }

    /**
     * Create oro_plist_curr_combined table
     *
     * @param Schema $schema
     */
    protected function createOroPlistCurrCombinedTable(Schema $schema)
    {
        $table = $schema->createTable('oro_plist_curr_combined');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_list_account_fallback table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListAccountFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_acc_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_id', 'website_id'], 'oro_price_list_acc_fb_unq');
    }

    /**
     * Create oro_price_list_acc_gr_fb table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListAccGroupFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_acc_gr_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_group_id', 'website_id'], 'oro_price_list_acc_gr_fb_unq');
    }

    /**
     * Create oro_price_list_website_fb table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListWebsiteFallbackTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_website_fb');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('fallback', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id'], 'oro_price_list_website_fb_unq');
    }

    /**
     * Create oro_cmb_price_list_to_acc table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPriceListToAccTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cmb_price_list_to_acc');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['account_id', 'website_id'], 'oro_cpl_to_acc_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cmb_plist_to_acc_gr table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cmb_plist_to_acc_gr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['account_group_id', 'website_id'], 'oro_cpl_to_acc_gr_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cmb_price_list_to_ws table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPriceListToWsTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cmb_price_list_to_ws');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['website_id'], 'oro_cpl_to_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cmb_pl_to_pl table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPlToPlTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cmb_pl_to_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('sort_order', 'integer', []);
        $table->addColumn('merge_allowed', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['combined_price_list_id', 'sort_order'], 'cmb_pl_to_pl_cmb_prod_sort_idx', []);
    }


    /**
     * Create oro_price_list_schedule table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deactivate_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['price_list_id'], 'IDX_C706756E5688DED7', []);
    }

    /**
     * Create oro_cpl_activation_rule table
     *
     * @param Schema $schema
     */
    protected function createOroCplActivationRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cpl_activation_rule');
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
     * @param Schema $schema
     */
    protected function createOroPriceAttributeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_attribute_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('field_name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroPriceAttributeCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('oro_product_attr_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_pl_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroPriceAttributeProductPriceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_attribute_price');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_pl_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addUniqueIndex(
            ['product_id', 'price_attribute_pl_id', 'quantity', 'unit_code', 'currency'],
            'oro_pricing_price_attribute_uidx'
        );
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_list_to_product table
     *
     * @param Schema $schema
     */
    protected function createOroriceListToProductTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_list_to_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('is_manual', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'oro_price_list_to_product_uidx');
    }

    /**
     * Add oro_price_list_currency foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceListCurrencyForeignKeys(Schema $schema)
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
     * Add oro_price_list_to_acc_group foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceListToAccGrForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_list_to_acc_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
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
     * Add oro_price_list_to_account foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceListToAccountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_list_to_account');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
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
     *
     * @param Schema $schema
     */
    protected function addOroPriceListToWebsiteForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroPriceProductForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroPriceProductCombinedForeignKeys(Schema $schema)
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
     * Add oro_price_product_minimal foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceProductMinimalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_product_minimal');
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
     *
     * @param Schema $schema
     */
    protected function addOroPlistCurrCombinedForeignKeys(Schema $schema)
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
     * Add oro_price_list_account_fallback foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceListAccountFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_list_acc_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
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
     * Add oro_price_list_acc_gr_fb foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceListAccGroupFallbackForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_list_acc_gr_fb');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
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
     *
     * @param Schema $schema
     */
    protected function addOroPriceListWebsiteFallbackForeignKeys(Schema $schema)
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
     * Add oro_cmb_plist_to_acc_gr foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmbPriceListToAccGrForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cmb_plist_to_acc_gr');
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
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
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
     *
     * @param Schema $schema
     */
    protected function addOroCmbPriceListToWsForeignKeys(Schema $schema)
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
     * Add oro_cmb_price_list_to_acc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmbPriceListToAccForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cmb_price_list_to_acc');
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
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cmb_pl_to_pl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmbPlToPlForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroPriceListScheduleForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroCplActivationRuleForeignKeys(Schema $schema)
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

    /**
     * @param Schema $schema
     */
    protected function addOroPriceAttributeCurrencyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_product_attr_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_attribute_pl'),
            ['price_attribute_pl_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroPriceAttributeProductPriceForeignKeys(Schema $schema)
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
     *
     * @param Schema $schema
     */
    protected function addOroriceListToProductForeignKeys(Schema $schema)
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
     * Create oro_price_rule table
     *
     * @param Schema $schema
     */
    protected function createOroPriceRuleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_price_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('rule_condition', 'text', ['notnull' => false]);
        $table->addColumn('rule', 'text', ['notnull' => true]);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('quantity_expression', 'text', ['notnull' => false]);
        $table->addColumn('currency_expression', 'text', ['notnull' => false]);
        $table->addColumn('product_unit_expression', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_price_rule_lexeme table
     *
     * @param Schema $schema
     */
    protected function createOroPriceRuleLexemeTable(Schema $schema)
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

    /**
     * Create oro_notification_message table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationMessageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_message');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('message', 'text', []);
        $table->addColumn('message_status', 'string', ['length' => 255]);
        $table->addColumn('channel', 'string', ['length' => 255]);
        $table->addColumn('receiver_entity_fqcn', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('receiver_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_resolved', 'boolean', []);
        $table->addColumn('resolved_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('topic', 'string', ['length' => 255]);
        $table->addIndex(['channel', 'topic'], 'oro_notif_msg_channel', []);
        $table->addIndex(['receiver_entity_fqcn', 'receiver_entity_id'], 'oro_notif_msg_entity', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_price_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPriceRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_price_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
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
     *
     * @param Schema $schema
     */
    protected function addOroPriceRuleLexemeForeignKeys(Schema $schema)
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
}
