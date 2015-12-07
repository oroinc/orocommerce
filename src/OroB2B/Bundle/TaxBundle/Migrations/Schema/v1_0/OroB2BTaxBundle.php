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
        $this->createOroB2BTaxAccountTaxCodeTable($schema);
        $this->createOroB2BTaxProductTaxCodeTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BTaxAccountTaxCodeForeignKeys($schema);
        $this->addOroB2BTaxProductTaxCodeForeignKeys($schema);
    }

    /**
     * Create orob2b_tax_account_tax_code table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxAccountTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_account_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E98BB26B77153098');
    }

    /**
     * Create orob2b_tax_product_tax_code table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxProductTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_product_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_5AF53A4A77153098');
    }

    /**
     * Add orob2b_tax_account_tax_code foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxAccountTaxCodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_account_tax_code');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_product_tax_code foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxProductTaxCodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_product_tax_code');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
