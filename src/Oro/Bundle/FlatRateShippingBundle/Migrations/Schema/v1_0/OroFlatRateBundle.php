<?php

namespace Oro\Bundle\FlatRateShippingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFlatRateBundle implements Migration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroFlatRateTransportLabelTable($schema);
        $this->addOroFlatRateTransportLabelForeignKeys($schema);
    }

    private function createOroFlatRateTransportLabelTable(Schema $schema)
    {
        if (!$schema->hasTable('oro_flat_rate_transport_label')) {
            $table = $schema->createTable('oro_flat_rate_transport_label');

            $table->addColumn('transport_id', 'integer', []);
            $table->addColumn('localized_value_id', 'integer', []);

            $table->setPrimaryKey(['transport_id', 'localized_value_id']);
            $table->addIndex(['transport_id'], 'oro_flat_rate_transport_label_transport_id', []);
            $table->addUniqueIndex(['localized_value_id'], 'oro_flat_rate_transport_label_localized_value_id', []);
        }
    }

    /**
     * @throws SchemaException
     */
    private function addOroFlatRateTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_flat_rate_transport_label');

        if (!$table->hasForeignKey('localized_value_id')) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_fallback_localization_val'),
                ['localized_value_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        }

        if (!$table->hasForeignKey('transport_id')) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_integration_transport'),
                ['transport_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        }
    }
}
