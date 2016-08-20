<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables update **/
        $this->createFieldTotalDiscountsAmount($schema);

        /** Tables generation **/
        $this->createOrob2BOrderDiscountTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BOrderDiscountForeignKeys($schema);
    }

    /**
     * Create field total_discounts_amount
     *
     * @param Schema $schema
     */
    protected function createFieldTotalDiscountsAmount(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addColumn('total_discounts_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
    }

    /**
     * Create orob2b_order_discount table
     * @param Schema $schema
     */
    protected function createOrob2BOrderDiscountTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_discount');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => true]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'percent',
            'percent',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:percent)']
        );
        $table->addColumn(
            'amount',
            'money',
            ['notnull' => true, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addIndex(['order_id'], 'IDX_F9A53B6A8D9F6D38', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_order_discount foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOrob2BOrderDiscountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_discount');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
