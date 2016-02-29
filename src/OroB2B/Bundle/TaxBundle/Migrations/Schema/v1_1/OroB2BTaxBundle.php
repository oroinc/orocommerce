<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Schema\v1_1;

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
        $this->createOroB2BTaxValueTable($schema);
        $this->createOroB2BTaxApplyTable($schema);

        $this->addOroB2BTaxApplyForeignKeys($schema);
    }

    /**
     * Create orob2b_tax_value table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('result', 'object', ['comment' => '(DC2Type:object)']);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('address', 'text', []);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_class', 'entity_id'], 'orob2b_tax_value_class_id_idx');
    }

    /**
     * Create orob2b_tax_apply table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxApplyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_apply');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_id', 'integer', ['notnull' => false]);
        $table->addColumn('tax_value_id', 'integer', ['notnull' => false]);
        $table->addColumn('rate', 'percent', ['comment' => '(DC2Type:percent)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('tax_amount', 'float', []);
        $table->addColumn('taxable_amount', 'float', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_tax_apply foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxApplyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_apply');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax'),
            ['tax_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_value'),
            ['tax_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
