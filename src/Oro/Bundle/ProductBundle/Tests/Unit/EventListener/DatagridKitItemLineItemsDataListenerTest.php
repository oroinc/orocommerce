<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitItemLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use PHPUnit\Framework\TestCase;

class DatagridKitItemLineItemsDataListenerTest extends TestCase
{
    private DatagridKitItemLineItemsDataListener $listener;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (Collection $values) => (string)$values->first());

        $this->listener = new DatagridKitItemLineItemsDataListener($localizationHelper);
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

    public function testOnLineItemDataWhenNoKitItem(): void
    {
        $kitItemLineItemId = 10;
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $kitItemLineItem = $this->createMock(ProductKitItemLineItemInterface::class);
        $kitItemLineItem
            ->method('getEntityIdentifier')
            ->willReturn($kitItemLineItemId);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([$kitItemLineItemId => $kitItemLineItem]);

        $event
            ->expects(self::once())
            ->method('addDataForLineItem')
            ->with($kitItemLineItemId, ['id' => 'kit_item_line_item:' . $kitItemLineItemId]);

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasKitItem(): void
    {
        $kitItemLineItemId = 10;
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $kitItem = (new ProductKitItemStub())
            ->setDefaultLabel('Sample Kit Item');
        $kitItemLineItem = $this->createMock(ProductKitItemLineItemInterface::class);
        $kitItemLineItem
            ->method('getEntityIdentifier')
            ->willReturn($kitItemLineItemId);
        $kitItemLineItem
            ->method('getKitItem')
            ->willReturn($kitItem);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([$kitItemLineItemId => $kitItemLineItem]);

        $event
            ->expects(self::once())
            ->method('addDataForLineItem')
            ->with(
                $kitItemLineItemId,
                ['id' => 'kit_item_line_item:' . $kitItemLineItemId, 'kitItemLabel' => $kitItem->getDefaultLabel()]
            );

        $this->listener->onLineItemData($event);
    }
}
