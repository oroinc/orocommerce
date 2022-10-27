<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroSaleQuoteDemandTable($schema);
        $this->addOroSaleQuoteDemandForeignKeys($schema);

        $this->updateAccountData($queries);
    }

    protected function updateAccountData(QueryBag $queries)
    {
        if ($this->platform instanceof MySqlPlatform) {
            $queries->addQuery('UPDATE oro_quote_demand d
                INNER JOIN oro_checkout_source s ON s.quoteDemand_id = d.id
                INNER JOIN oro_checkout c ON c.source_id = s.id
                SET d.account_id = c.customer_id, d.account_user_id = c.customer_user_id');
        } else {
            $queries->addQuery('UPDATE oro_quote_demand d
                SET account_id = c.customer_id, account_user_id = c.customer_user_id
                FROM oro_checkout_source s INNER JOIN oro_checkout c ON c.source_id = s.id
                WHERE s.quoteDemand_id = d.id');
        }

        $queries->addQuery('DELETE FROM oro_quote_demand WHERE account_id IS NULL AND account_user_id IS NULL');
    }

    protected function updateOroSaleQuoteDemandTable(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_demand');
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
    }

    protected function addOroSaleQuoteDemandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
