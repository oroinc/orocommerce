<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class DatagridLineItemsDataEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductLineItemInterface */
    private $lineItem1;

    /** @var ProductLineItemInterface */
    private $lineItem2;

    /** @var ProductLineItemInterface */
    private $lineItem3;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var DatagridLineItemsDataEvent */
    private $event;

    protected function setUp(): void
    {
        $this->lineItem1 = $this->createMock(ProductLineItemInterface::class);
        $this->lineItem2 = $this->createMock(ProductLineItemInterface::class);
        $this->lineItem3 = $this->createMock(ProductLineItemInterface::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagrid
            ->expects($this->any())
            ->method('getName')
            ->willReturn('test-grid');

        $this->event = new DatagridLineItemsDataEvent(
            [$this->lineItem1, $this->lineItem2, $this->lineItem3],
            $this->datagrid,
            ['context']
        );
    }

    public function testGetLineItems(): void
    {
        $this->assertSame([$this->lineItem1, $this->lineItem2, $this->lineItem3], $this->event->getLineItems());
    }

    public function testGetDatagrid(): void
    {
        $this->assertSame($this->datagrid, $this->event->getDatagrid());
    }

    public function testGetContext(): void
    {
        $this->assertSame(['context'], $this->event->getContext());
    }

    public function testLineItemData(): void
    {
        $this->assertEquals([], $this->event->getDataForLineItem(42));

        $this->event->addDataForLineItem(42, ['name' => 'value1']);
        $this->assertEquals(['name' => 'value1'], $this->event->getDataForLineItem(42));

        $this->event->addDataForLineItem(42, ['name' => 'value2']);
        $this->assertEquals(['name' => 'value2'], $this->event->getDataForLineItem(42));

        $this->event->setDataForLineItem(42, ['name2' => 'value2', 'name3' => 'value3']);
        $this->assertEquals(
            ['name2' => 'value2', 'name3' => 'value3'],
            $this->event->getDataForLineItem(42)
        );

        $this->assertEquals(
            [42 => ['name2' => 'value2', 'name3' => 'value3']],
            $this->event->getDataForAllLineItems()
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            DatagridLineItemsDataEvent::NAME . '.test-grid',
            $this->event->getName()
        );
    }
}
