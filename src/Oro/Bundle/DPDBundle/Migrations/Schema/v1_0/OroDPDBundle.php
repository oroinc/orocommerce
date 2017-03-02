<?php

namespace Oro\Bundle\DPDBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDPDBundle implements Migration
{
    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /* update tables */
        $this->updateOroIntegrationTransportTable($schema);

        /* Tables generation **/
        $this->createOroDpdRateTable($schema);
        $this->createOroDpdShippingServiceTable($schema);
        $this->createOroDpdShippingTransactionTable($schema);
        $this->createOroDpdTransportLabelTable($schema);
        $this->createOroDpdTransportShipServiceTable($schema);

        /* Foreign keys generation **/
        $this->addOroDpdRateForeignKeys($schema);
        $this->addOroDpdShippingTransactionForeignKeys($schema);
        $this->addOroDpdTransportLabelForeignKeys($schema);
        $this->addOroDpdTransportShipServiceForeignKeys($schema);
        $this->addOroIntegrationTransportForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('dpd_live_mode', 'boolean', ['notnull' => false]);
        $table->addColumn('dpd_cloud_user_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('dpd_cloud_user_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('dpd_label_size', 'string', ['notnull' => false, 'length' => 10]);
        $table->addColumn('dpd_label_start_position', 'string', ['notnull' => false, 'length' => 20]);
        $table->addColumn('dpd_unit_of_weight_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('dpd_rate_policy', 'smallint', ['notnull' => false]);
        $table->addColumn(
            'dpd_flat_rate_price_value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'dpd_invalidate_cache_at',
            'datetime',
            ['notnull' => false, 'comment' => '(DC2Type:datetime)']
        );
        $table->addIndex(['dpd_unit_of_weight_code'], 'IDX_D7A389A894296FE', []);
    }

    /**
     * Create oro_dpd_rate table.
     *
     * @param Schema $schema
     */
    protected function createOroDpdRateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_rate');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('shipping_service_id', 'string', ['notnull' => false, 'length' => 30]);
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['length' => 2]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('weight_value', 'float', ['notnull' => false]);
        $table->addColumn('price_value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['transport_id'], 'IDX_651D84679909C13F', []);
        $table->addIndex(['shipping_service_id'], 'IDX_651D846755A7F9B8', []);
        $table->addIndex(['country_code'], 'IDX_651D8467F026BB7C', []);
        $table->addIndex(['region_code'], 'IDX_651D8467AEB327AF', []);
    }

    /**
     * Create oro_dpd_shipping_service table.
     *
     * @param Schema $schema
     */
    protected function createOroDpdShippingServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_shipping_service');
        $table->addColumn('code', 'string', ['length' => 30]);
        $table->addColumn('description', 'string', ['length' => 255]);
        $table->addColumn('is_express', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_dpd_shipping_transaction table.
     *
     * @param Schema $schema
     */
    protected function createOroDpdShippingTransactionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_shipping_transaction');
        $table->addColumn('id', 'string', ['length' => 36]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addColumn('parcel_numbers', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['order_id'], 'IDX_230C58E68D9F6D38', []);
        $table->addIndex(['file_id'], 'IDX_230C58E693CB796C', []);
    }

    /**
     * Create oro_dpd_transport_label table.
     *
     * @param Schema $schema
     */
    protected function createOroDpdTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_transport_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_localized_value_id');
        $table->addIndex(['transport_id'], 'IDX_transport_id', []);
    }

    /**
     * Create oro_dpd_transport_ship_service table.
     *
     * @param Schema $schema
     */
    protected function createOroDpdTransportShipServiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_dpd_transport_ship_service');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('ship_service_id', 'string', ['length' => 30]);
        $table->setPrimaryKey(['transport_id', 'ship_service_id']);
    }

    /**
     * Add oro_dpd_rate foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDpdRateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dpd_rate');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dpd_shipping_service'),
            ['shipping_service_id'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add oro_dpd_shipping_transaction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDpdShippingTransactionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dpd_shipping_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_dpd_transport_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroDpdTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_dpd_transport_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
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
            ['code'],
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
     * Add oro_integration_transport foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroIntegrationTransportForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_weight_unit'),
            ['dpd_unit_of_weight_code'],
            ['code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
