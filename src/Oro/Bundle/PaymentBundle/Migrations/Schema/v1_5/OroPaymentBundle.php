<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_5;

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
        $this->updatePaymentTransactionTable($schema);
        $this->addConstraintsToPaymentTransactionTable($schema, $queries);
    }

    /**
     * @param Schema $schema
     */
    protected function updatePaymentTransactionTable(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_transaction');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
    }

    /**
     * @param Schema $schema
     */
    protected function addConstraintsToPaymentTransactionTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
