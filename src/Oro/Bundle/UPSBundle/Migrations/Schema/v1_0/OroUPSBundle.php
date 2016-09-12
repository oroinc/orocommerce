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
        $this->createOroUPSTransportShipServiceTable($schema);
        $this->addOroIntegrationTransportForeignKeys($schema);
        $this->addOroUPSShippingServiceForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('ups_base_url', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_user', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_api_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_shipping_account_number', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('ups_shipping_account_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ups_pickup_type', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('ups_unit_of_weight', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('ups_country_code', 'string', ['notnull' => false, 'length' => 2]);
    }

    /**
     * @param Schema $schema
     */
    public function createOroUPSShippingServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ups_shipping_service');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 10]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['country_code', 'code']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroUPSTransportShipServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ups_transport_ship_service');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('ship_service_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'ship_service_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroIntegrationTransportForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['ups_country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroUPSShippingServiceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ups_shipping_service');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }
}
