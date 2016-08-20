<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPaymentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPaymentTransactionTable($schema);

        $this->addOroPaymentTransactionForeignKeys($schema);
    }

    /**
     * Create table for PaymentTransaction entity
     *
     * @param Schema $schema
     */
    protected function createOroPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_payment_transaction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer', []);
        $table->addColumn('access_identifier', 'string', ['length' => 255]);
        $table->addColumn('access_token', 'string', ['length' => 255]);
        $table->addColumn('payment_method', 'string', ['length' => 255]);
        $table->addColumn('action', 'string', ['length' => 255]);
        $table->addColumn('reference', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('amount', 'string', ['length' => 255]);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('active', 'boolean', []);
        $table->addColumn('successful', 'boolean', []);
        $table->addColumn('source_payment_transaction', 'integer', ['notnull' => false]);
        $table->addColumn('request', 'secure_array', ['notnull' => false]);
        $table->addColumn('response', 'secure_array', ['notnull' => false]);
        $table->addColumn('transaction_options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['source_payment_transaction']);
    }

    /**
     * Add orob2b_payment_transaction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaymentTransactionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_payment_transaction'),
            ['source_payment_transaction'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
