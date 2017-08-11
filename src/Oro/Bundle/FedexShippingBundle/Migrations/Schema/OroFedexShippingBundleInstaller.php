<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFedexShippingBundleInstaller implements Installation
{
    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritDoc}
     *
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createShippingServiceTable($schema);
        $this->createOroFedexTransportLabelTable($schema);
        $this->updateOroIntegrationTransportTable($schema);
        $this->createOroFedexShippingServiceTable($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createShippingServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_fedex_shipping_service');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 200]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 200]);

        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    private function createOroFedexTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_fedex_transport_label');

        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_fedex_transport_label_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_fedex_transport_label_localized_value_id', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    private function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');

        $table->addColumn('fedex_key', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('fedex_password', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('fedex_account_number', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('fedex_meter_number', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('fedex_pickup_type', 'string', ['notnull' => false, 'length' => 100]);
        $table->addColumn('fedex_unit_of_weight', 'string', ['notnull' => false, 'length' => 3]);
    }

    /**
     * @param Schema $schema
     */
    private function createOroFedexShippingServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_fedex_transp_ship_service');

        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('ship_service_id', 'integer', []);

        $table->setPrimaryKey(['transport_id', 'ship_service_id']);
        $table->addIndex(['ship_service_id'], 'oro_fedex_transp_ship_service_id');
    }
}
