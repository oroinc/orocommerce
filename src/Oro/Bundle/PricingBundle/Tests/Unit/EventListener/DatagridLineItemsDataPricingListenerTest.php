<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * Adds pricing data to the DatagridLineItemsDataEvent.
 */
class DatagridLineItemsDataPricingListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendProductPricesDataProvider;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var DatagridLineItemsDataPricingListener */
    private $listener;

    protected function setUp(): void
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->listener = new DatagridLineItemsDataPricingListener(
            $this->frontendProductPricesDataProvider,
            $this->numberFormatter
        );
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoPrices(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $lineItems = [new LineItem(), new LineItem()];
        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->frontendProductPricesDataProvider
            ->expects($this->once())
            ->method('getProductsMatchedPrice')
            ->with($lineItems)
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemData(): void
    {
        $lineItem1 = $this->getLineItem(1, 10, 'item');
        $lineItem2 = $this->getLineItem(2, 100, 'each');
        $lineItem3 = $this->getLineItem(3, 1, 'piece');
        $lineItems = [$lineItem1, $lineItem2, $lineItem3];

        $event = new DatagridLineItemsDataEvent($lineItems, $this->createMock(DatagridInterface::class), []);

        $this->frontendProductPricesDataProvider
            ->expects($this->once())
            ->method('getProductsMatchedPrice')
            ->with($lineItems)
            ->willReturn(
                [
                    10 => ['item' => Price::create(111, 'USD')],
                    20 => ['each' => Price::create(222, 'USD')],
                ]
            );

        $this->numberFormatter
            ->expects($this->exactly(4))
            ->method('formatCurrency')
            ->willReturnCallback(
                static function ($value, $currency) {
                    return $value . $currency;
                }
            );

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'price' => '111USD',
                'subtotal' => '1110USD',
                'currency' => 'USD',
                'subtotalValue' => 1110,
            ],
            $event->getDataForLineItem(1)
        );

        $this->assertEquals(
            [
                'price' => '222USD',
                'subtotal' => '22200USD',
                'currency' => 'USD',
                'subtotalValue' => 22200,
            ],
            $event->getDataForLineItem(2)
        );

        $this->assertEquals([], $event->getDataForLineItem(3));
    }

    /**
     * @param int $id
     * @param int $quantity
     * @param string $unitCode
     * @return LineItem
     */
    private function getLineItem(int $id, int $quantity, string $unitCode): LineItem
    {
        $product = $this->getEntity(Product::class, ['id' => $id * 10]);
        $productUnit = (new ProductUnit())->setCode($unitCode);

        return $this->getEntity(
            LineItem::class,
            ['id' => $id, 'product' => $product, 'quantity' => $quantity, 'unit' => $productUnit]
        );
    }
}
