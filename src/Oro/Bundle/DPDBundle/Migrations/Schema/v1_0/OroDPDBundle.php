<?php

namespace Oro\Bundle\DPDBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDPDBundle implements Migration
{
    /**
     * {@inheritdoc}
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** update tables */
        $this->updateOroIntegrationTransportTable($schema);

        /** create tables */
        $this->createOroDPDTransportLabelTable($schema);
        $this->createOroDPDShippingServiceTable($schema);
        $this->createOroDpdTransportShipServiceTable($schema);
        //$this->createOroDpdShippingTransactionTable($schema); TODO

        /** foreing keys */
        $this->addOroDPDTransportLabelForeignKeys($schema);
        $this->addOroDpdTransportShipServiceForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('dpd_live_mode', 'boolean', ['notnull' => false]);
        $table->addColumn('dpd_cloud_user_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('dpd_cloud_user_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('dpd_label_size', 'string', ['notnull' => false, 'length' => 10]);
        $table->addColumn('dpd_label_start_position', 'string', ['notnull' => false, 'length' => 20]);
        $table->addColumn(
            'dpd_invalidate_cache_at',
            'datetime',
            ['notnull' => false, 'comment' => '(DC2Type:datetime)']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroDPDTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_transport_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'IDX_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_localized_value_id', []);
    }

    /**
     * @param Schema $schema
     */
    public function createOroDPDShippingServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_shipping_service');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['notnull' => true, 'length' => 10]);
        $table->addColumn('description', 'string', ['notnull' => true, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_dpd_transport_ship_service table
     *
     * @param Schema $schema
     */
    protected function createOroDpdTransportShipServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_transport_ship_service');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('ship_service_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'ship_service_id']);
        $table->addIndex(['transport_id'], 'IDX_269F87B9909C13F', []);
        $table->addIndex(['ship_service_id'], 'IDX_269F87B37CA9B1D', []);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroDPDTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dpd_transport_label');
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
     * Add oro_dpd_transport_ship_service foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDpdTransportShipServiceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dpd_transport_ship_service');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dpd_shipping_service'),
            ['ship_service_id'],
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
}
