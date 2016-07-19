<?php

namespace OroB2B\Bundle\WarehouseBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BWarehouseBundle implements Migration, ExtendExtensionAwareInterface
{
    const WAREHOUSE_TABLE_NAME = 'orob2b_warehouse';
    const ORDER_TABLE_NAME = 'orob2b_order';
    const ORDER_LINE_ITEM_TABLE_NAME = 'orob2b_order_line_item';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable(self::ORDER_TABLE_NAME) || !$schema->hasTable(self::ORDER_LINE_ITEM_TABLE_NAME)) {
            return;
        }

        $warehouseTable = $schema->getTable(self::WAREHOUSE_TABLE_NAME);
        $orderTable = $schema->getTable(self::ORDER_TABLE_NAME);
        $orderLineItemTable = $schema->getTable(self::ORDER_LINE_ITEM_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $orderTable,
            'warehouse',
            $warehouseTable,
            'id',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]
            ]
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $orderLineItemTable,
            'warehouse',
            $warehouseTable,
            'id',
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'without_default' => true]
            ]
        );
    }
}
