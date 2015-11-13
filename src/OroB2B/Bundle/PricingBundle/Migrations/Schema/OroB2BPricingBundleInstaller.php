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

        /** Foreign keys generation **/
        $this->addOrob2BPriceListCurrencyForeignKeys($schema);
        $this->addOrob2BPriceListToAccGrForeignKeys($schema);
        $this->addOrob2BPriceListToAccountForeignKeys($schema);
        $this->addOrob2BPriceListToWebsiteForeignKeys($schema);
        $this->addOrob2BPriceProductForeignKeys($schema);
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
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
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
        $table->addIndex(['price_list_id'], 'IDX_F468ECAA5688DED7', []);
    }

    /**
     * Create orob2b_price_list_to_acc_gr table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_acc_gr');
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $table->setPrimaryKey(['price_list_id', 'website_id', 'account_group_id']);
        $table->addIndex(['account_group_id'], 'IDX_D016F9E3869A3BF1', []);
        $table->addIndex(['price_list_id'], 'IDX_D016F9E35688DED7', []);
        $table->addIndex(['website_id'], 'IDX_D016F9E318F45C82', []);
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
        $table->setPrimaryKey(['price_list_id', 'website_id', 'account_id']);
        $table->addIndex(['account_id'], 'IDX_B5472D719B6B5FBA', []);
        $table->addIndex(['price_list_id'], 'IDX_B5472D715688DED7', []);
        $table->addIndex(['website_id'], 'IDX_B5472D7118F45C82', []);
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
        $table->setPrimaryKey(['price_list_id', 'website_id']);
        $table->addIndex(['price_list_id'], 'IDX_8F1E26325688DED7', []);
        $table->addIndex(['website_id'], 'IDX_8F1E263218F45C82', []);
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
        $table->addIndex(['price_list_id'], 'IDX_BCDE766D5688DED7', []);
        $table->addIndex(['product_id'], 'IDX_BCDE766D4584665A', []);
        $table->addIndex(['unit_code'], 'IDX_BCDE766DFBD3D1C2', []);
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
     * Add orob2b_price_list_to_acc_gr foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListToAccGrForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_to_acc_gr');
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
}
