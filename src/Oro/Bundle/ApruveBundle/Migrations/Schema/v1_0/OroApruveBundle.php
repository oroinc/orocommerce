<?php

namespace Oro\Bundle\Apruve\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroApruveBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);

        /** Tables generation **/
        $this->createOroApruveShortLabelTable($schema);
        $this->createOroApruveTransLabelTable($schema);

        /** Foreign keys generation **/
        $this->addOroApruveShortLabelForeignKeys($schema);
        $this->addOroApruveTransLabelForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('apruve_test_mode', 'boolean', ['notnull' => false, 'default' => false]);
        $table->addColumn('apruve_merchant_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('apruve_api_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('apruve_webhook_token', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create oro_apruve_short_label table
     *
     * @param Schema $schema
     */
    protected function createOroApruveShortLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_apruve_short_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_1FAEF591EB576E89');
        $table->addIndex(['transport_id'], 'IDX_1FAEF5919909C13F', []);
    }

    /**
     * Create oro_apruve_trans_label table
     *
     * @param Schema $schema
     */
    protected function createOroApruveTransLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_apruve_trans_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_2068304BEB576E89');
        $table->addIndex(['transport_id'], 'IDX_2068304B9909C13F', []);
    }

    /**
     * Add oro_apruve_short_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroApruveShortLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_apruve_short_label');
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
     * Add oro_apruve_trans_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroApruveTransLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_apruve_trans_label');
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
