<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\LineItemDataListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemDataListenerTest extends \PHPUnit\Framework\TestCase
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

    /** @var LineItemDataListener */
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

        $this->listener = new LineItemDataListener(
            $this->upcomingProductProvider,
            $this->lowInventoryProvider,
            $this->formatter,
            $this->localeSettings
        );
    }

    public function testOnLineItemData(): void
    {
        $product = $this->createMock(Product::class);

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

        $event = new LineItemDataEvent([$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            ['isUpcoming' => true, 'availabilityDate' => 'Jun 10, 2020', 'isLowInventory' => false],
            $event->getDataForLineItem(42)
        );
    }

    public function testOnLineItemDataWithoutAvailabilityDate(): void
    {
        $product = $this->createMock(Product::class);

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

        $event = new LineItemDataEvent([$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(['isUpcoming' => true, 'isLowInventory' => true], $event->getDataForLineItem(42));
    }

    public function testOnLineItemDataNotUpcoming(): void
    {
        $product = $this->createMock(Product::class);

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

        $event = new LineItemDataEvent([$this->getEntity(LineItem::class, ['id' => 42, 'product' => $product])]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(['isUpcoming' => false, 'isLowInventory' => true], $event->getDataForLineItem(42));
    }
}
