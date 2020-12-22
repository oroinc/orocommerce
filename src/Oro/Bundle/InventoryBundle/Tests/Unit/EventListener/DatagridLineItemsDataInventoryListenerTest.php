<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\InventoryBundle\EventListener\DatagridLineItemsDataInventoryListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\InventoryBundle\Tests\Unit\Stubs\ProductStub;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class DatagridLineItemsDataInventoryListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \DateTime */
    private $availabilityDate;

    /** @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $upcomingProductProvider;

    /** @var LowInventoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lowInventoryProvider;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var DatagridLineItemsDataInventoryListener */
    private $listener;

    protected function setUp(): void
    {
        $this->availabilityDate = new \DateTime();

        $this->upcomingProductProvider = $this->createMock(UpcomingProductProvider::class);
        $this->lowInventoryProvider = $this->createMock(LowInventoryProvider::class);

        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->formatter->expects($this->any())
            ->method('formatDate')
            ->with($this->availabilityDate, null, null, 'Europe/London')
            ->willReturn('Jun 10, 2020');

        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->localeSettings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('Europe/London');

        $this->listener = new DatagridLineItemsDataInventoryListener(
            $this->upcomingProductProvider,
            $this->lowInventoryProvider,
            $this->formatter,
            $this->localeSettings
        );
    }

    public function testOnLineItemDataWithoutProduct(): void
    {
        $this->upcomingProductProvider->expects($this->never())
            ->method($this->anything());

        $this->lowInventoryProvider->expects($this->never())
            ->method($this->anything());

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42])],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals([], $event->getDataForLineItem(42));
    }

    public function testOnLineItemData(): void
    {
        $product = new ProductStub(1);
        $inventoryStatus = new InventoryStatusStub('in_stock', 'In Stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->upcomingProductProvider->expects($this->once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(true);

        $this->upcomingProductProvider->expects($this->once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn($this->availabilityDate);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(false);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'isUpcoming' => true,
                'availabilityDate' => 'Jun 10, 2020',
                'isLowInventory' => false,
                'inventoryStatus' => 'in_stock',
            ],
            $event->getDataForLineItem(42)
        );
    }

    public function testOnLineItemDataWithoutAvailabilityDate(): void
    {
        $product = new ProductStub(1);
        $inventoryStatus = new InventoryStatusStub('in_stock', 'In Stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->upcomingProductProvider->expects($this->once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(true);

        $this->upcomingProductProvider->expects($this->once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn(null);

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(true);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'isUpcoming' => true,
                'isLowInventory' => true,
                'inventoryStatus' => 'in_stock',
            ],
            $event->getDataForLineItem(42)
        );
    }

    public function testOnLineItemDataNotUpcoming(): void
    {
        $product = new ProductStub(1);
        $inventoryStatus = new InventoryStatusStub('in_stock', 'In Stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->upcomingProductProvider->expects($this->once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(false);

        $this->upcomingProductProvider->expects($this->never())
            ->method('getAvailabilityDate');

        $this->lowInventoryProvider->expects($this->once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(true);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'isUpcoming' => false,
                'isLowInventory' => true,
                'inventoryStatus' => 'in_stock',
            ],
            $event->getDataForLineItem(42)
        );
    }
}
