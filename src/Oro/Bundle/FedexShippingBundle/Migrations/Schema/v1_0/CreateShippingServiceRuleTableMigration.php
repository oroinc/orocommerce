<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateShippingServiceRuleTableMigration implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_fedex_ship_service_rule');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('limitation_expression_lbs', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('limitation_expression_kg', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('service_type', 'string', ['notnull' => false, 'length' => 250]);
        $table->addColumn('residential_address', 'boolean', ['notnull' => true, 'default' => false]);

        $table->setPrimaryKey(['id']);
    }
}
