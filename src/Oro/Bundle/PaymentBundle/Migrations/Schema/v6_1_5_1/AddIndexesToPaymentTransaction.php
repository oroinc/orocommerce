<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v6_1_5_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIndexesToPaymentTransaction implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_payment_transaction');
        if (!$table) {
            return;
        }

        if (!$table->hasIndex('idx_pay_txn_cls_ident_method')) {
            $table->addIndex(['entity_class', 'entity_identifier', 'payment_method'], 'idx_pay_txn_cls_ident_method');
        }
        if (!$table->hasIndex('idx_pay_txn_cls_ident_id')) {
            $table->addIndex(['entity_class', 'entity_identifier', 'id'], 'idx_pay_txn_cls_ident_id');
        }
    }
}
