<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateShippingServiceTableMigration implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_fedex_shipping_service');
        
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 200]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 200]);
        $table->addColumn('limitation_expression_lbs', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('limitation_expression_kg', 'string', ['notnull' => true, 'length' => 250]);
        
        $table->setPrimaryKey(['id']);
    }
}
