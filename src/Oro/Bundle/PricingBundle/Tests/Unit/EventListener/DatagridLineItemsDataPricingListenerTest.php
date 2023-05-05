<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedDTO;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Stub\LineItemPriceAwareStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataPricingListenerTest extends TestCase
{
    use EntityTrait;

    private const CURRENCY_USD = 'USD';
    private const EMPTY_DATA = [
        DatagridLineItemsDataPricingListener::PRICE_VALUE => null,
        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => null,
        DatagridLineItemsDataPricingListener::PRICE => null,
        DatagridLineItemsDataPricingListener::SUBTOTAL => null,
    ];

    private LineItemNotPricedSubtotalProvider|MockObject $lineItemNotPricedSubtotalProvider;

    private DatagridLineItemsDataPricingListener $listener;

    protected function setUp(): void
    {
        $this->lineItemNotPricedSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $numberFormatter = $this->createMock(NumberFormatter::class);

        $this->listener = new DatagridLineItemsDataPricingListener(
            $this->lineItemNotPricedSubtotalProvider,
            $roundingService,
            $numberFormatter
        );

        $numberFormatter
            ->method('formatCurrency')
            ->willReturnCallback(static fn ($value, $currency) => $value . $currency);

        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));
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

    public function testOnLineItemDataWhenNoSubtotal(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $lineItems = [$this->getLineItem(10, 1, 'item'), $this->getLineItem(20, 2, 'item')];
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->lineItemNotPricedSubtotalProvider
            ->expects(self::once())
            ->method('getSubtotal')
            ->with(new LineItemsNotPricedDTO(new ArrayCollection($lineItems)))
            ->willReturn(null);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoPrices(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $lineItems = [$this->getLineItem(10, 1, 'item'), $this->getLineItem(20, 2, 'item')];
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $subtotal = new Subtotal();
        $subtotal->setCurrency(self::CURRENCY_USD);

        $this->lineItemNotPricedSubtotalProvider
            ->expects(self::once())
            ->method('getSubtotal')
            ->with(new LineItemsNotPricedDTO(new ArrayCollection($lineItems)))
            ->willReturn($subtotal);

        $event
            ->expects(self::exactly(2))
            ->method('addDataForLineItem')
            ->withConsecutive(
                [
                    $lineItems[0]->getEntityIdentifier(),
                    self::identicalTo(self::EMPTY_DATA),
                ],
                [
                    $lineItems[1]->getEntityIdentifier(),
                    self::identicalTo(self::EMPTY_DATA),
                ]
            );

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemData(): void
    {
        $lineItem1 = $this->getLineItem(1, 10, 'item');
        $lineItem2 = $this->getLineItem(2, 100, 'each');
        $lineItem3 = $this->getLineItem(3, 1, 'piece');
        $lineItems = [
            $lineItem1->getEntityIdentifier() => $lineItem1,
            $lineItem2->getEntityIdentifier() => $lineItem2,
            $lineItem3->getEntityIdentifier() => $lineItem3,
        ];

        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(DatagridInterface::class), []);

        $subtotal = new Subtotal();
        $subtotal->setCurrency(self::CURRENCY_USD);
        $lineItem1Hash = spl_object_hash($lineItem1);
        $lineItem2Hash = spl_object_hash($lineItem2);
        $lineItem3Hash = spl_object_hash($lineItem3);
        $subtotalData = [
            $lineItem1Hash => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 111.0,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 1110.0,
            ],
            $lineItem2Hash => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 222.0,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 22200.0,
            ],
            $lineItem3Hash => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 0.0,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 0.0,
            ],
        ];
        $subtotal->setData($subtotalData);

        $this->lineItemNotPricedSubtotalProvider
            ->expects(self::once())
            ->method('getSubtotal')
            ->with(new LineItemsNotPricedDTO(new ArrayCollection($lineItems)))
            ->willReturn($subtotal);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                    $subtotalData[$lineItem1Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE],
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE =>
                    $subtotalData[$lineItem1Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL],
                DatagridLineItemsDataPricingListener::PRICE =>
                    $subtotalData[$lineItem1Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE] . 'USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL =>
                    $subtotalData[$lineItem1Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL] . 'USD',
            ],
            $event->getDataForLineItem(1)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                    $subtotalData[$lineItem2Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE],
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE =>
                    $subtotalData[$lineItem2Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL],
                DatagridLineItemsDataPricingListener::PRICE =>
                    $subtotalData[$lineItem2Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE] . 'USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL =>
                    $subtotalData[$lineItem2Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL] . 'USD',
            ],
            $event->getDataForLineItem(2)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                    $subtotalData[$lineItem3Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE],
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE =>
                    $subtotalData[$lineItem3Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL],
                DatagridLineItemsDataPricingListener::PRICE =>
                    $subtotalData[$lineItem3Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE] . 'USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL =>
                    $subtotalData[$lineItem3Hash][LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL] . 'USD',
            ],
            $event->getDataForLineItem(3)
        );
    }

    public function testOnLineItemDataWhenLineItemIsPriceAware(): void
    {
        $lineItem1 = $this->getLineItem(1, 10, 'item', 555);
        $lineItem2 = $this->getLineItem(2, 100, 'each', 777);
        $lineItem3 = $this->getLineItem(3, 1, 'piece', 999);
        $lineItems = [
            $lineItem1->getEntityIdentifier() => $lineItem1,
            $lineItem2->getEntityIdentifier() => $lineItem2,
            $lineItem3->getEntityIdentifier() => $lineItem3,
        ];

        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(DatagridInterface::class), []);

        $subtotal = new Subtotal();
        $subtotal->setCurrency(self::CURRENCY_USD);

        $this->lineItemNotPricedSubtotalProvider
            ->expects(self::once())
            ->method('getSubtotal')
            ->with(new LineItemsNotPricedDTO(new ArrayCollection($lineItems)))
            ->willReturn($subtotal);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 555.0,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 5550.0,
                DatagridLineItemsDataPricingListener::PRICE => '555USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL => '5550USD',
            ],
            $event->getDataForLineItem(1)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 777.0,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 77700.0,
                DatagridLineItemsDataPricingListener::PRICE => '777USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL => '77700USD',
            ],
            $event->getDataForLineItem(2)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 999.0,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 999.0,
                DatagridLineItemsDataPricingListener::PRICE => '999USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL => '999USD',
            ],
            $event->getDataForLineItem(3)
        );
    }

    private function getLineItem(int $id, int $quantity, string $unit, float $price = null): ProductLineItemInterface
    {
        $product = $this->getEntity(Product::class, ['id' => $id * 10]);
        $productUnit = (new ProductUnit())->setCode($unit);

        $data = ['id' => $id, 'product' => $product, 'quantity' => $quantity, 'productUnit' => $productUnit];
        if ($price) {
            $data['price'] = Price::create($price, self::CURRENCY_USD);
        }

        return $this->getEntity(LineItemPriceAwareStub::class, $data);
    }
}
