<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_1;

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
        $this->createOroUPSTransportLabelTable($schema);
        $this->addOroUpsTransportLabelForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroUPSTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ups_transport_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'IDX_1554DDE9909C13D', []);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_1554DDE37CA9B1F', []);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroUpsTransportLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ups_transport_label');
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
}
