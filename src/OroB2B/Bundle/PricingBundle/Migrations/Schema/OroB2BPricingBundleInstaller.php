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
        return 'v1_1';
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
        $this->createOrob2BPlistCurrCombinedTable($schema);
        $this->createOrob2BCmbPriceListToAccTable($schema);
        $this->createOrob2BCmbPriceListToAccGrTable($schema);
        $this->createOrob2BCmbPriceListToWsTable($schema);
        $this->createOrob2BCmbPlToPlTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BPriceListCurrencyForeignKeys($schema);
        $this->addOrob2BPriceListToAccGrForeignKeys($schema);
        $this->addOrob2BPriceListToAccountForeignKeys($schema);
        $this->addOrob2BPriceListToWebsiteForeignKeys($schema);
        $this->addOrob2BPriceProductForeignKeys($schema);
        $this->addOrob2BPriceProductCombinedForeignKeys($schema);
        $this->addOrob2BPlistCurrCombinedForeignKeys($schema);
        $this->addOrob2BCmbPriceListToAccGrForeignKeys($schema);
        $this->addOrob2BCmbPriceListToWsForeignKeys($schema);
        $this->addOrob2BCmbPriceListToAccForeignKeys($schema);
        $this->addOrob2BCmbPlToPlForeignKeys($schema);
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
        $table->addColumn('merge_allowed', 'boolean');
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
        $table->addColumn('merge_allowed', 'boolean');
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
        $table->addColumn('merge_allowed', 'boolean');
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
        $table->addColumn('merge', 'boolean', []);
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
     * Create orob2b_cmb_price_list_to_acc table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPriceListToAccTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_price_list_to_acc');
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['account_id', 'combined_price_list_id', 'website_id']);
    }

    /**
     * Create orob2b_cmb_plist_to_acc_gr table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_plist_to_acc_gr');
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['account_group_id', 'combined_price_list_id', 'website_id']);
    }

    /**
     * Create orob2b_cmb_price_list_to_ws table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmbPriceListToWsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_price_list_to_ws');
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['combined_price_list_id', 'website_id']);
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
}
