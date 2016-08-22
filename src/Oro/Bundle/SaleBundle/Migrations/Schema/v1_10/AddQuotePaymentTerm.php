<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddQuotePaymentTerm implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addColumn('payment_term_id', 'integer', ['notnull' => false]);
        $table->addIndex(['payment_term_id'], 'IDX_4F66B6F617653B16', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_payment_term'),
            ['payment_term_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
