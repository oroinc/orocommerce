<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUPSBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);
        $this->createOroUPSShippingServiceTable($schema);
        $this->addOroUPSShippingServiceForeignKeys($schema);
    }

    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('ups_base_url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_user', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_shipping_account_number', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('ups_shipping_account_name', 'string', ['notnull' => false, 'length' => 255]);
    }

    public function createOroUPSShippingServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_integration_ups_service');
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 10]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('transport_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['code']);
    }

    protected function addOroUPSShippingServiceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_ups_service');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
