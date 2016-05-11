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
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->getColumn('request')->setOptions(['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->getColumn('response')->setOptions(['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
    }
}
