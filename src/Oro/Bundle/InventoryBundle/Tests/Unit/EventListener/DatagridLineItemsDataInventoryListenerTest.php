<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\InventoryBundle\EventListener\DatagridLineItemsDataInventoryListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class DatagridLineItemsDataInventoryListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private \DateTime $availabilityDate;

    private UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject $upcomingProductProvider;

    private LowInventoryProvider|\PHPUnit\Framework\MockObject\MockObject $lowInventoryProvider;

    private DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter;

    private LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings;

    private DatagridLineItemsDataInventoryListener $listener;

    protected function setUp(): void
    {
        $this->availabilityDate = new \DateTime();

        $this->upcomingProductProvider = $this->createMock(UpcomingProductProvider::class);
        $this->lowInventoryProvider = $this->createMock(LowInventoryProvider::class);

        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->formatter->expects(self::any())
            ->method('formatDate')
            ->with($this->availabilityDate, null, null, 'Europe/London')
            ->willReturn('Jun 10, 2020');

        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->localeSettings->expects(self::any())
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
        $this->upcomingProductProvider->expects(self::never())
            ->method($this->anything());

        $this->lowInventoryProvider->expects(self::never())
            ->method($this->anything());

        $event = new DatagridLineItemsDataEvent(
            [42 => $this->getEntity(LineItem::class, ['id' => 42])],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertEquals([], $event->getDataForLineItem(42));
    }

    public function testOnLineItemData(): void
    {
        $product = $this->createProduct();

        $this->upcomingProductProvider->expects(self::once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(true);

        $this->upcomingProductProvider->expects(self::once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn($this->availabilityDate);

        $this->lowInventoryProvider->expects(self::once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(false);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                'isUpcoming' => true,
                'availabilityDate' => 'Jun 10, 2020',
                'isLowInventory' => false,
                'inventoryStatus' => 'in_stock',
                'minimumQuantityToOrder' => 1,
                'maximumQuantityToOrder' => 10,
            ],
            $event->getDataForLineItem(42)
        );
    }

    public function testOnLineItemDataWithoutAvailabilityDate(): void
    {
        $product = $this->createProduct();

        $this->upcomingProductProvider->expects(self::once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(true);

        $this->upcomingProductProvider->expects(self::once())
            ->method('getAvailabilityDate')
            ->with($product)
            ->willReturn(null);

        $this->lowInventoryProvider->expects(self::once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(true);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                'isUpcoming' => true,
                'isLowInventory' => true,
                'inventoryStatus' => 'in_stock',
                'minimumQuantityToOrder' => 1,
                'maximumQuantityToOrder' => 10,
            ],
            $event->getDataForLineItem(42)
        );
    }

    public function testOnLineItemDataNotUpcoming(): void
    {
        $product = $this->createProduct();

        $this->upcomingProductProvider->expects(self::once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(false);

        $this->upcomingProductProvider->expects(self::never())
            ->method('getAvailabilityDate');

        $this->lowInventoryProvider->expects(self::once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(true);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                'isUpcoming' => false,
                'isLowInventory' => true,
                'inventoryStatus' => 'in_stock',
                'minimumQuantityToOrder' => 1,
                'maximumQuantityToOrder' => 10,
            ],
            $event->getDataForLineItem(42)
        );
    }

    public function testOnLineItemDataWithoutQuantityToOrder(): void
    {
        $product = new ProductStub();
        $inventoryStatus = new InventoryStatus('in_stock', 'In Stock');
        $product->setInventoryStatus($inventoryStatus);

        $this->upcomingProductProvider->expects(self::once())
            ->method('isUpcoming')
            ->with($product)
            ->willReturn(false);

        $this->lowInventoryProvider->expects(self::once())
            ->method('isLowInventoryProduct')
            ->with($product)
            ->willReturn(true);

        $event = new DatagridLineItemsDataEvent(
            [$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                'isUpcoming' => false,
                'isLowInventory' => true,
                'inventoryStatus' => 'in_stock',
                'minimumQuantityToOrder' => null,
                'maximumQuantityToOrder' => null,
            ],
            $event->getDataForLineItem(42)
        );
    }

    private function createProduct(): ProductStub
    {
        $product = new ProductStub();
        $inventoryStatus = new InventoryStatus('in_stock', 'In Stock');
        $product->setInventoryStatus($inventoryStatus);

        $minimumQuantityFallback = new EntityFieldFallbackValue();
        $minimumQuantityFallback->setScalarValue(1);
        $product->setMinimumQuantityToOrder($minimumQuantityFallback);

        $maximumQuantityFallback = new EntityFieldFallbackValue();
        $maximumQuantityFallback->setScalarValue(10);
        $product->setMaximumQuantityToOrder($maximumQuantityFallback);

        return $product;
    }
}
