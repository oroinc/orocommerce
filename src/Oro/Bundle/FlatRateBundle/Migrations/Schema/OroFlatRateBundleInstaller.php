<?php

namespace Oro\Bundle\FlatRateBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroFlatRateBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroFlatRateTransportLabelTable($schema);

        /** Foreign keys generation **/
        $this->addOroFlatRateTransportLabelForeignKeys($schema);
    }

    /**
     * Create oro_flat_rate_transport_label table
     *
     * @param Schema $schema
     */
    protected function createOroFlatRateTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_flat_rate_transport_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_flat_rate_transport_label_localized_value_id');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_flat_rate_transport_label_transport_id', []);
    }

    /**
     * Add oro_flat_rate_transport_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroFlatRateTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_flat_rate_transport_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
