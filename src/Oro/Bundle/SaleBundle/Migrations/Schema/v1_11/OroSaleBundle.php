<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroSaleQuoteDemandTable($schema);
        $this->addOroSaleQuoteDemandForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroSaleQuoteDemandTable(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_demand');
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['account_user_id']);
        $table->addIndex(['account_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroSaleQuoteDemandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
