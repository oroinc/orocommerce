<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BTaxBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BTaxTaxTable($schema);
        $this->createOrob2BTaxAccTaxCodeAccTable($schema);
        $this->createOrob2BTaxAccountTaxCodeTable($schema);
        $this->createOrob2BTaxProdTaxCodeProdTable($schema);
        $this->createOrob2BTaxProductTaxCodeTable($schema);
        $this->createOroB2BTaxJurisdictionTable($schema);
        $this->createOrob2BTaxZipCodeTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BTaxAccTaxCodeAccForeignKeys($schema);
        $this->addOrob2BTaxProdTaxCodeProdForeignKeys($schema);
        $this->addOroB2BTaxJurisdictionForeignKeys($schema);
        $this->addOrob2BTaxZipCodeForeignKeys($schema);
    }

    /**
     * Create orob2b_tax_acc_tax_code_acc table
     *
     * @param Schema $schema
     */
    protected function createOrob2BTaxAccTaxCodeAccTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_acc_tax_code_acc');
        $table->addColumn('account_tax_code_id', 'integer', []);
        $table->addColumn('account_id', 'integer', []);
        $table->setPrimaryKey(['account_tax_code_id', 'account_id']);
        $table->addUniqueIndex(['account_id'], 'UNIQ_53167F2A9B6B5FBA');
    }

    /**
     * Create orob2b_tax_account_tax_code table
     *
     * @param Schema $schema
     */
    protected function createOrob2BTaxAccountTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_account_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E98BB26B77153098');
    }

    /**
     * Create orob2b_tax_prod_tax_code_prod table
     *
     * @param Schema $schema
     */
    protected function createOrob2BTaxProdTaxCodeProdTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_prod_tax_code_prod');
        $table->addColumn('product_tax_code_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['product_tax_code_id', 'product_id']);
        $table->addUniqueIndex(['product_id'], 'UNIQ_3CF9D1FA4584665A');
    }

    /**
     * Create orob2b_tax_product_tax_code table
     *
     * @param Schema $schema
     */
    protected function createOrob2BTaxProductTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_product_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_5AF53A4A77153098');
    }

    /**
     * Create orob2b_tax_tax table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxTaxTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_tax');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('rate', 'percent', ['comment' => '(DC2Type:percent)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E132B24377153098');
    }

    /**
     * Create orob2b_tax_jurisdiction table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxJurisdictionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_jurisdiction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_2CBEF9AE77153098');
    }

    /**
     * Create orob2b_tax_zip_code table
     *
     * @param Schema $schema
     */
    protected function createOrob2BTaxZipCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_zip_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_jurisdiction_id', 'integer', []);
        $table->addColumn('zip_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('zip_range_start', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('zip_range_end', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_tax_acc_tax_code_acc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BTaxAccTaxCodeAccForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_acc_tax_code_acc');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_prod_tax_code_prod foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BTaxProdTaxCodeProdForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_prod_tax_code_prod');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_product_tax_code'),
            ['product_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_jurisdiction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxJurisdictionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_jurisdiction');
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
     * Add orob2b_tax_zip_code foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BTaxZipCodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_zip_code');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_jurisdiction'),
            ['tax_jurisdiction_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
