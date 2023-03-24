<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
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

    private FrontendProductPricesDataProvider|MockObject $frontendProductPricesDataProvider;

    private NumberFormatter|MockObject $numberFormatter;

    private DatagridLineItemsDataPricingListener $listener;

    protected function setUp(): void
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $userCurrencyManager
            ->method('getUserCurrency')
            ->willReturn(self::CURRENCY_USD);

        $this->listener = new DatagridLineItemsDataPricingListener(
            $this->frontendProductPricesDataProvider,
            $userCurrencyManager,
            $this->numberFormatter
        );
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

    public function testOnLineItemDataWhenNoPrices(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $lineItems = [$this->getLineItem(10, 1, 'item'), $this->getLineItem(20, 2, 'item')];
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->frontendProductPricesDataProvider
            ->expects(self::once())
            ->method('getProductsMatchedPrice')
            ->with($lineItems)
            ->willReturn([]);

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

        $this->frontendProductPricesDataProvider
            ->expects(self::once())
            ->method('getProductsMatchedPrice')
            ->with($lineItems)
            ->willReturn(
                [
                    10 => ['item' => Price::create(111.0, self::CURRENCY_USD)],
                    20 => ['each' => Price::create(222.0, self::CURRENCY_USD)],
                ]
            );

        $this->numberFormatter
            ->expects(self::exactly(4))
            ->method('formatCurrency')
            ->willReturnCallback(static fn ($value, $currency) => $value . $currency);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 111.0,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 1110.0,
                DatagridLineItemsDataPricingListener::PRICE => '111USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL => '1110USD',
            ],
            $event->getDataForLineItem(1)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 222.0,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 22200.0,
                DatagridLineItemsDataPricingListener::PRICE => '222USD',
                DatagridLineItemsDataPricingListener::SUBTOTAL => '22200USD',
            ],
            $event->getDataForLineItem(2)
        );

        self::assertSame(self::EMPTY_DATA, $event->getDataForLineItem(3));
    }

    public function testOnLineItemDataFixedPrices(): void
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

        $this->frontendProductPricesDataProvider
            ->expects(self::once())
            ->method('getProductsMatchedPrice')
            ->with($lineItems)
            ->willReturn(
                [
                    10 => ['item' => Price::create(111.0, self::CURRENCY_USD)],
                    20 => ['each' => Price::create(222.0, self::CURRENCY_USD)],
                ]
            );

        $this->numberFormatter
            ->expects(self::exactly(6))
            ->method('formatCurrency')
            ->willReturnCallback(
                static function ($value, $currency) {
                    return $value . $currency;
                }
            );

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
