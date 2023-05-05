<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DatagridKitLineItemsDataListenerTest extends TestCase
{
    private EventDispatcherInterface|MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new DatagridKitLineItemsDataListener($this->eventDispatcher);
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotKitItemLineItem(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([10 => new \stdClass()]);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotKit(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([10 => $this->createMock(ProductKitItemLineItemsAwareInterface::class)]);
        $event
            ->expects(self::once())
            ->method('getDataForLineItem')
            ->with(10)
            ->willReturn(['type' => Product::TYPE_SIMPLE]);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoKitItemLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $productKitLineItem = $this->createMock(ProductKitItemLineItemsAwareInterface::class);
        $productKitLineItem
            ->method('getKitItemLineItems')
            ->willReturn(new ArrayCollection());
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([10 => $productKitLineItem]);
        $event
            ->expects(self::once())
            ->method('getDataForLineItem')
            ->with(10)
            ->willReturn(['type' => Product::TYPE_KIT]);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasKitItemLineItems(): void
    {
        $lineItemId = 10;
        $kitItemLineItem1 = new ProductKitItemLineItemStub(100);
        $kitItemLineItem2 = new ProductKitItemLineItemStub(200);
        $productKitLineItem = (new ProductKitItemLineItemsAwareStub($lineItemId))
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $event = new DatagridLineItemsDataEvent(
            [$lineItemId => $productKitLineItem],
            [$lineItemId => ['type' => Product::TYPE_KIT]],
            $this->createMock(Datagrid::class),
            []
        );

        $kitItemLineItemsDataEvent = new DatagridLineItemsDataEvent(
            [
                $kitItemLineItem1->getEntityIdentifier() => $kitItemLineItem1,
                $kitItemLineItem2->getEntityIdentifier() => $kitItemLineItem2,
            ],
            [],
            $event->getDatagrid(),
            []
        );
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($kitItemLineItemsDataEvent, $kitItemLineItemsDataEvent->getName())
            ->willReturnCallback(
                function (DatagridLineItemsDataEvent $event) use ($kitItemLineItem1, $kitItemLineItem2) {
                    $event->addDataForLineItem(
                        $kitItemLineItem1->getEntityIdentifier(),
                        ['sample_key1' => 'sample_value1']
                    );
                    $event->addDataForLineItem(
                        $kitItemLineItem2->getEntityIdentifier(),
                        ['sample_key2' => 'sample_value2']
                    );

                    return $event;
                }
            );

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                $lineItemId => [
                    'type' => Product::TYPE_KIT,
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [
                        ['sample_key1' => 'sample_value1'],
                        ['sample_key2' => 'sample_value2'],
                    ],
                ]
            ],
            $event->getDataForAllLineItems()
        );
    }
}
