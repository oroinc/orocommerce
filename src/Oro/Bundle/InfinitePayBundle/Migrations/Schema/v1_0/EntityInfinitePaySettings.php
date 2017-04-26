<?php

namespace Oro\Bundle\InfinitePayBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class EntityInfinitePaySettings implements Migration
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables update */
        $this->updateOroIntegrationTransportTable($schema);

        /** Tables generation **/
        $this->createOroInfinitepayLblTable($schema);
        $this->createOroInfinitepayShortLblTable($schema);

        /** Foreign keys generation **/
        $this->addOroInfinitepayLblForeignKeys($schema);
        $this->addOroInfinitepayShortLblForeignKeys($schema);
    }

    /**
     * Create oro_infinitepay_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroInfinitepayLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_infinitepay_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_A5EE03E2EB576E89');
        $table->addIndex(['transport_id'], 'IDX_A5EE03E29909C13F', []);
    }

    /**
     * Create oro_infinitepay_short_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroInfinitepayShortLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_infinitepay_short_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_1C78A0ACEB576E89');
        $table->addIndex(['transport_id'], 'IDX_1C78A0AC9909C13F', []);
    }

    /**
     * Update oro_integration_transport table
     *
     * @param Schema $schema
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('ipay_client_ref', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ipay_username', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ipay_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ipay_secret', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ipay_auto_capture', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('ipay_auto_activate', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('ipay_debug_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('ipay_invoice_due_period', 'smallint', ['notnull' => false]);
        $table->addColumn('ipay_invoice_shipping_duration', 'smallint', ['notnull' => false]);
        $table->addColumn('ipay_test_mode', 'boolean', ['default' => '0', 'notnull' => false]);
    }

    /**
     * Add oro_infinitepay_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroInfinitepayLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_infinitepay_lbl');
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
     * Add oro_infinitepay_short_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroInfinitepayShortLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_infinitepay_short_lbl');
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
}
