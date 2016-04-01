<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;

class OroB2BPaymentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BPaymentTransactionTable($schema);
    }

    /**
     * Create table for PaymentTransaction entity
     *
     * @param Schema $schema
     */
    protected function createOroB2BPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_payment_transaction');
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('reference', Type::STRING, ['notnull' => false]);
        $table->addColumn('state', Type::STRING, ['notnull' => false]);
        $table->addColumn('type', Type::STRING);
        $table->addColumn('entity_class', Type::STRING);
        $table->addColumn('entity_identifier', Type::INTEGER);
        $table->addColumn('data', SecureArrayType::TYPE, ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_class', 'entity_identifier'], 'orob2b_payment_tran_entity_idx', []);
    }
}
