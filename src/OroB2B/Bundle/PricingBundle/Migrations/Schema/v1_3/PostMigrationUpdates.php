<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class PostMigrationUpdates implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updatePriceListTable($schema, $queries);
        $this->updatePriceListCombinedTable($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function updatePriceListTable(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_list SET contain_schedule=FALSE WHERE contain_schedule IS NULL'
            )
        );

        $table = $schema->getTable('orob2b_price_list');
        $table->getColumn('contain_schedule')->setNotnull(true);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function updatePriceListCombinedTable(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_list_combined SET is_prices_calculated=TRUE WHERE is_prices_calculated IS NULL'
            )
        );

        $table = $schema->getTable('orob2b_price_list_combined');
        $table->getColumn('is_prices_calculated')->setNotnull(true);
    }
}
