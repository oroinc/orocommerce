<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema\v1_3;

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
        $this->addIndexToPaymentTransactionTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addIndexToPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addUniqueIndex(['access_identifier', 'access_token'], 'access_idx');
    }
}
