<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateShippingServiceTableMigration implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_fedex_shipping_service');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 200]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 200]);
        $table->addColumn('rule_id', 'integer', ['notnull' => true]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fedex_ship_service_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );

        $table->setPrimaryKey(['id']);
    }
}
