<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroTaxBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroTaxTable($schema);
        $this->createOroTaxAccGrpTcAccGrpTable($schema);
        $this->createOroTaxAccTaxCodeAccTable($schema);
        $this->createOroTaxAccountTaxCodeTable($schema);
        $this->createOroTaxJurisdictionTable($schema);
        $this->createOroTaxProdTaxCodeProdTable($schema);
        $this->createOroTaxProductTaxCodeTable($schema);
        $this->createOroTaxRuleTable($schema);
        $this->createOroTaxZipCodeTable($schema);

        /** Foreign keys generation **/
        $this->addOroTaxAccGrpTcAccGrpForeignKeys($schema);
        $this->addOroTaxAccTaxCodeAccForeignKeys($schema);
        $this->addOroTaxJurisdictionForeignKeys($schema);
        $this->addOroTaxProdTaxCodeProdForeignKeys($schema);
        $this->addOroTaxRuleForeignKeys($schema);
        $this->addOroTaxZipCodeForeignKeys($schema);
    }

    /**
     * Create orob2b_tax table
     *
     * @param Schema $schema
     */
    protected function createOroTaxTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax');
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
     * Create orob2b_tax_acc_grp_tc_acc_grp table
     *
     * @param Schema $schema
     */
    protected function createOroTaxAccGrpTcAccGrpTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_acc_grp_tc_acc_grp');
        $table->addColumn('account_group_tax_code_id', 'integer', []);
        $table->addColumn('account_group_id', 'integer', []);
        $table->setPrimaryKey(['account_group_tax_code_id', 'account_group_id']);
        $table->addUniqueIndex(['account_group_id'], 'UNIQ_D3457B7869A3BF1');
    }

    /**
     * Create orob2b_tax_acc_tax_code_acc table
     *
     * @param Schema $schema
     */
    protected function createOroTaxAccTaxCodeAccTable(Schema $schema)
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
    protected function createOroTaxAccountTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_account_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E98BB26B77153098');
    }

    /**
     * Create orob2b_tax_jurisdiction table
     *
     * @param Schema $schema
     */
    protected function createOroTaxJurisdictionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_jurisdiction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_2CBEF9AE77153098');
    }

    /**
     * Create orob2b_tax_prod_tax_code_prod table
     *
     * @param Schema $schema
     */
    protected function createOroTaxProdTaxCodeProdTable(Schema $schema)
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
    protected function createOroTaxProductTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_product_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_5AF53A4A77153098');
    }

    /**
     * Create orob2b_tax_rule table
     *
     * @param Schema $schema
     */
    protected function createOroTaxRuleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_jurisdiction_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_tax_code_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_tax_code_id', 'integer', ['notnull' => false]);
        $table->addColumn('tax_id', 'integer', ['notnull' => false]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_tax_zip_code table
     *
     * @param Schema $schema
     */
    protected function createOroTaxZipCodeTable(Schema $schema)
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
     * Add orob2b_tax_acc_grp_tc_acc_grp foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxAccGrpTcAccGrpForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_acc_grp_tc_acc_grp');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_group_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_acc_tax_code_acc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxAccTaxCodeAccForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_acc_tax_code_acc');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_jurisdiction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxJurisdictionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_jurisdiction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_prod_tax_code_prod foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxProdTaxCodeProdForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_prod_tax_code_prod');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_product_tax_code'),
            ['product_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_jurisdiction'),
            ['tax_jurisdiction_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_product_tax_code'),
            ['product_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax'),
            ['tax_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_zip_code foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTaxZipCodeForeignKeys(Schema $schema)
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
