<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataEventTest extends TestCase
{
    private const LINE_ITEM_1_ID = 10;
    private const LINE_ITEM_2_ID = 20;
    private const LINE_ITEM_3_ID = 30;

    private ProductLineItemInterface|MockObject $lineItem1;

    private ProductLineItemInterface|MockObject $lineItem2;

    private ProductLineItemInterface|MockObject $lineItem3;

    private DatagridInterface|MockObject $datagrid;

    private DatagridLineItemsDataEvent $event;

    protected function setUp(): void
    {
        $this->lineItem1 = $this->createMock(ProductLineItemInterface::class);
        $this->lineItem2 = $this->createMock(ProductLineItemInterface::class);
        $this->lineItem3 = $this->createMock(ProductLineItemInterface::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagrid
            ->method('getName')
            ->willReturn('test-grid');

        $this->event = new DatagridLineItemsDataEvent(
            [
                self::LINE_ITEM_1_ID => $this->lineItem1,
                self::LINE_ITEM_2_ID => $this->lineItem2,
                self::LINE_ITEM_3_ID => $this->lineItem3
            ],
            [
                self::LINE_ITEM_1_ID => ['type' => Product::TYPE_SIMPLE],
                self::LINE_ITEM_2_ID => ['type' => Product::TYPE_CONFIGURABLE],
                self::LINE_ITEM_3_ID => ['type' => Product::TYPE_KIT]
            ],
            $this->datagrid,
            ['context']
        );
    }

    public function testGetLineItems(): void
    {
        self::assertSame(
            [
                self::LINE_ITEM_1_ID => $this->lineItem1,
                self::LINE_ITEM_2_ID => $this->lineItem2,
                self::LINE_ITEM_3_ID => $this->lineItem3
            ],
            $this->event->getLineItems()
        );
    }

    public function testGetDatagrid(): void
    {
        self::assertSame($this->datagrid, $this->event->getDatagrid());
    }

    public function testGetContext(): void
    {
        self::assertSame(['context'], $this->event->getContext());
    }

    public function testLineItemData(): void
    {
        self::assertEquals(['type' => Product::TYPE_SIMPLE], $this->event->getDataForLineItem(self::LINE_ITEM_1_ID));

        $this->event->addDataForLineItem(self::LINE_ITEM_1_ID, ['name' => 'value1']);
        self::assertEquals(
            ['name' => 'value1', 'type' => Product::TYPE_SIMPLE],
            $this->event->getDataForLineItem(self::LINE_ITEM_1_ID)
        );

        $this->event->addDataForLineItem(self::LINE_ITEM_1_ID, ['name' => 'value2']);
        self::assertEquals(
            ['name' => 'value2', 'type' => Product::TYPE_SIMPLE],
            $this->event->getDataForLineItem(self::LINE_ITEM_1_ID)
        );

        $this->event->setDataForLineItem(self::LINE_ITEM_1_ID, ['name2' => 'value2', 'name3' => 'value3']);
        self::assertEquals(
            ['name2' => 'value2', 'name3' => 'value3'],
            $this->event->getDataForLineItem(self::LINE_ITEM_1_ID)
        );

        self::assertEquals(
            [
                self::LINE_ITEM_1_ID => ['name2' => 'value2', 'name3' => 'value3'],
                self::LINE_ITEM_2_ID => ['type' => Product::TYPE_CONFIGURABLE],
                self::LINE_ITEM_3_ID => ['type' => Product::TYPE_KIT],
            ],
            $this->event->getDataForAllLineItems()
        );
    }

    public function testGetName(): void
    {
        self::assertEquals(
            DatagridLineItemsDataEvent::NAME . '.test-grid',
            $this->event->getName()
        );
    }
}
