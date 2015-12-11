<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BTaxBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BTaxAccTaxCodeAccTable($schema);
        $this->createOrob2BTaxAccountTaxCodeTable($schema);
        $this->createOrob2BTaxProdTaxCodeProdTable($schema);
        $this->createOrob2BTaxProductTaxCodeTable($schema);
        $this->createOroB2BTaxTaxTable($schema);
        $this->createOroB2BTaxTaxRuleTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BTaxAccTaxCodeAccForeignKeys($schema);
        $this->addOrob2BTaxProdTaxCodeProdForeignKeys($schema);
        $this->addOroB2BTaxTaxRuleForeignKeys($schema);
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
        $table->addColumn('rate', 'float', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E132B24377153098');
    }

    /**
     * Create orob2b_tax_tax_rule table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxTaxRuleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_tax_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_tax_code_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_tax_code_id', 'integer', ['notnull' => false]);
        $table->addColumn('description', 'text', ['notnull' => false]);
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
     * Add orob2b_tax_tax_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxTaxRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_tax_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_tax'),
            ['tax_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_tax_code_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_product_tax_code'),
            ['product_tax_code_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
