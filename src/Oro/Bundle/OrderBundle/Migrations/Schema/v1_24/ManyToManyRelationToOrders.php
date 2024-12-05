<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds many-to-many relation between orders and:
 *  - line items
 *  - shipping tracking
 */
class ManyToManyRelationToOrders implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroLineItemsTable($schema);
        $this->createOroShippingTrackingsTable($schema);
        $this->addOroLineItemsForeignKeys($schema);
        $this->addOroShippingTrackingsKeys($schema);

        $queries->addQuery($this->getFillLineItemsQuery());
        $queries->addQuery($this->getFillTrackingsQuery());

        $queries->addQuery('ALTER TABLE oro_order_line_item DROP CONSTRAINT fk_32715f0b8d9f6d38');
        $queries->addQuery('DROP INDEX idx_de9136098d9f6d38');
        $queries->addQuery('ALTER TABLE oro_order_line_item DROP order_id');

        $queries->addQuery('ALTER TABLE oro_order_shipping_tracking DROP CONSTRAINT fk_e1da8e528d9f6d38');
        $queries->addQuery('DROP INDEX idx_e1da8e528d9f6d38');
        $queries->addQuery('ALTER TABLE oro_order_shipping_tracking DROP order_id');
    }

    private function createOroLineItemsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_line_items');
        $table->addColumn('order_id', 'integer');
        $table->addColumn('line_item_id', 'integer');
        $table->setPrimaryKey(['line_item_id', 'order_id']);
        $table->addIndex(['order_id'], 'IDX_order_id395');
        $table->addIndex(['line_item_id'], 'IDX_line_item_id2AC');
    }

    private function createOroShippingTrackingsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_shipping_trackings');
        $table->addColumn('order_id', 'integer');
        $table->addColumn('tracking_id', 'integer');
        $table->setPrimaryKey(['tracking_id', 'order_id']);
        $table->addIndex(['order_id'], 'IDX_order_id454');
        $table->addIndex(['tracking_id'], 'IDX_line_item_id2AT');
    }

    private function addOroLineItemsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_line_items');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    private function addOroShippingTrackingsKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_shipping_trackings');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_shipping_tracking'),
            ['tracking_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    private function getFillLineItemsQuery(): string
    {
        return 'INSERT INTO oro_order_line_items (line_item_id, order_id)'
            . ' SELECT id, order_id'
            . ' FROM oro_order_line_item'
            . ' WHERE order_id IS NOT NULL';
    }

    private function getFillTrackingsQuery(): string
    {
        return 'INSERT INTO oro_order_shipping_trackings (tracking_id, order_id)'
            . ' SELECT id, order_id'
            . ' FROM oro_order_shipping_tracking'
            . ' WHERE order_id IS NOT NULL';
    }
}
