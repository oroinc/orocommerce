<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_0_0_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDraftSessionFields implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addDraftFieldsToOrder($schema);
        $this->addDraftFieldsToOrderLineItem($schema);
    }

    private function addDraftFieldsToOrder(Schema $schema): void
    {
        $table = $schema->getTable('oro_order');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }

        if (!$table->hasColumn('draft_source_id')) {
            $table->addColumn('draft_source_id', 'integer', ['notnull' => false]);
            $table->addForeignKeyConstraint(
                $table,
                ['draft_source_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }

    private function addDraftFieldsToOrderLineItem(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_line_item');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }

        if (!$table->hasColumn('draft_source_id')) {
            $table->addColumn('draft_source_id', 'integer', ['notnull' => false]);
            $table->addForeignKeyConstraint(
                $table,
                ['draft_source_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }

        if (!$table->hasColumn('draft_delete')) {
            $table->addColumn('draft_delete', 'boolean', ['notnull' => true, 'default' => false]);
        }
    }
}
