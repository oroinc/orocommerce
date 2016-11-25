<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPaymentBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPaymentTransactionTable($schema);
        $this->createOroPaymentStatusTable($schema);

        $this->addOroPaymentTransactionForeignKeys($schema);
    }

    /**
     * Create table for PaymentTransaction entity
     *
     * @param Schema $schema
     */
    protected function createOroPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_transaction');
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
        $table->addColumn('request', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('response', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('transaction_options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['access_identifier', 'access_token'], 'oro_pay_trans_access_uidx');
        $table->addIndex(['source_payment_transaction']);
    }

    /**
     * Add oro_payment_transaction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaymentTransactionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_transaction'),
            ['source_payment_transaction'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['frontend_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Create oro_payment_status
     *
     * @param Schema $schema
     */
    protected function createOroPaymentStatusTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer', []);
        $table->addColumn('payment_status', 'string', ['length' => 255]);
        $table->addUniqueIndex(['entity_class', 'entity_identifier'], 'oro_payment_status_unique');
        $table->setPrimaryKey(['id']);
    }
}
