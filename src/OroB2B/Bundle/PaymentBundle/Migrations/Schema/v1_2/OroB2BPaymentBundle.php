<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BPaymentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updatePaymentTransactionTable($schema);
        $this->addConstraintsToPaymentTransactionTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updatePaymentTransactionTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->getColumn('request')->setOptions(['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->getColumn('response')->setOptions(['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
    }

    /**
     * @param Schema $schema
     */
    protected function addConstraintsToPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
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
    }
}
