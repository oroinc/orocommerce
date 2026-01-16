<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\PricingBundle\Tests\Unit\Stub\LineItemPriceAwareStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitItemLineItemsDataListener;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataPricingListenerTest extends TestCase
{
    use EntityTrait;

    private const CURRENCY_USD = 'USD';
    private const EMPTY_DATA = [
        DatagridLineItemsDataPricingListener::PRICE_VALUE => null,
        DatagridLineItemsDataPricingListener::CURRENCY => null,
        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => null,
        DatagridLineItemsDataPricingListener::PRICE => null,
        DatagridLineItemsDataPricingListener::SUBTOTAL => null,
    ];

    private ProductLineItemPriceProviderInterface|MockObject $productLineItemsPriceProvider;

    private ProductLineItemsHolderFactoryInterface|MockObject $productLineItemsHolderFactory;

    private DatagridLineItemsDataPricingListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->productLineItemsPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $numberFormatter = $this->createMock(NumberFormatter::class);
        $this->productLineItemsHolderFactory = $this->createMock(ProductLineItemsHolderFactoryInterface::class);

        $this->listener = new DatagridLineItemsDataPricingListener(
            $this->productLineItemsPriceProvider,
            $this->productLineItemsHolderFactory,
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

    public function testOnLineItemDataWhenNoPrices(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $lineItems = [$this->getLineItem(10, 1, 'item'), $this->getLineItem(20, 2, 'item')];
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $this->productLineItemsHolderFactory
            ->expects(self::once())
            ->method('createFromLineItems')
            ->with($lineItems)
            ->willReturn($lineItemsHolder);

        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPricesForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn([]);

        $event
            ->expects(self::exactly(2))
            ->method('setDataForLineItem')
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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

        $kitItem1 = new ProductKitItemStub(100);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1);
        $kitItem2 = new ProductKitItemStub(200);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2000))
            ->setKitItem($kitItem2);
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $lineItem2->getEntityIdentifier() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [
                        [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1],
                        [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2],
                    ],
                ],
            ],
            $this->createMock(DatagridInterface::class),
            []
        );

        $lineItem1Price = new ProductLineItemPrice($lineItem1, Price::create(111.0, self::CURRENCY_USD), 1110.0);

        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            Price::create(1001.1234, self::CURRENCY_USD),
            1001.1234
        );
        $kitItemLineItem2Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem2,
            Price::create(0.0, self::CURRENCY_USD),
            0.0
        );
        $lineItem2Price = (new ProductKitLineItemPrice($lineItem2, Price::create(222.0, self::CURRENCY_USD), 12231.234))
            ->addKitItemLineItemPrice($kitItemLineItem1Price)
            ->addKitItemLineItemPrice($kitItemLineItem2Price);

        $lineItem3Price = new ProductKitLineItemPrice($lineItem3, Price::create(0.0, self::CURRENCY_USD), 0.0);

        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $this->productLineItemsHolderFactory
            ->expects(self::once())
            ->method('createFromLineItems')
            ->with($lineItems)
            ->willReturn($lineItemsHolder);

        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPricesForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn([
                $lineItem1->getEntityIdentifier() => $lineItem1Price,
                $lineItem2->getEntityIdentifier() => $lineItem2Price,
                $lineItem3->getEntityIdentifier() => $lineItem3Price,
            ]);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $lineItem1Price->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $lineItem1Price->getSubtotal(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $lineItem1Price->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL => $lineItem1Price->getSubtotal() . self::CURRENCY_USD,
            ],
            $event->getDataForLineItem(1)
        );

        self::assertSame(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    [
                        DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1,
                        DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                            $kitItemLineItem1Price->getPrice()->getValue(),
                        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitItemLineItem1Price->getSubtotal(),
                        DatagridLineItemsDataPricingListener::PRICE =>
                            $kitItemLineItem1Price->getPrice()->getValue() . self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL =>
                            $kitItemLineItem1Price->getSubtotal() . self::CURRENCY_USD,
                    ],
                    [
                        DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2,
                        DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                            $kitItemLineItem2Price->getPrice()->getValue(),
                        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitItemLineItem2Price->getSubtotal(),
                        DatagridLineItemsDataPricingListener::PRICE =>
                            $kitItemLineItem2Price->getPrice()->getValue() . self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL =>
                            $kitItemLineItem2Price->getSubtotal() . self::CURRENCY_USD,
                    ],
                ],
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $lineItem2Price->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $lineItem2Price->getSubtotal(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $lineItem2Price->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL => $lineItem2Price->getSubtotal() . self::CURRENCY_USD,
            ],
            $event->getDataForLineItem(2)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $lineItem3Price->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $lineItem3Price->getSubtotal(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $lineItem3Price->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL => $lineItem3Price->getSubtotal() . self::CURRENCY_USD,
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

        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $this->productLineItemsHolderFactory
            ->expects(self::once())
            ->method('createFromLineItems')
            ->with($lineItems)
            ->willReturn($lineItemsHolder);

        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPricesForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn([]);

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

    public function testOnLineItemDataWhenKitItemLineItemHasNoDefinedPrice(): void
    {
        $kitLineItem = $this->getLineItem(1, 10, 'item');
        $lineItems = [
            $kitLineItem->getEntityIdentifier() => $kitLineItem,
        ];

        $kitItem1 = new ProductKitItemStub(100);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1);
        $kitItem2 = new ProductKitItemStub(200);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2000))
            ->setKitItem($kitItem2);
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $kitLineItem->getEntityIdentifier() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [
                        [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1],
                        [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2],
                    ],
                ],
            ],
            $this->createMock(DatagridInterface::class),
            []
        );

        $kitItemLineItem1Price = new ProductKitItemLineItemPrice(
            $kitItemLineItem1,
            Price::create(1001.1234, self::CURRENCY_USD),
            1001.1234
        );
        $kitLineItemPrice = (new ProductKitLineItemPrice(
            $kitLineItem,
            Price::create(111.0, self::CURRENCY_USD),
            1110.0
        ))
            ->addKitItemLineItemPrice($kitItemLineItem1Price);

        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $this->productLineItemsHolderFactory
            ->expects(self::once())
            ->method('createFromLineItems')
            ->with($lineItems)
            ->willReturn($lineItemsHolder);

        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPricesForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn([$kitLineItem->getEntityIdentifier() => $kitLineItemPrice]);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    [
                        DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1,
                        DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                            $kitItemLineItem1Price->getPrice()->getValue(),
                        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitItemLineItem1Price->getSubtotal(),
                        DatagridLineItemsDataPricingListener::PRICE =>
                            $kitItemLineItem1Price->getPrice()->getValue() . self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL =>
                            $kitItemLineItem1Price->getSubtotal() . self::CURRENCY_USD,
                    ],
                    [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2] + self::EMPTY_DATA,
                ],
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $kitLineItemPrice->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitLineItemPrice->getSubtotal(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $kitLineItemPrice->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL =>
                    $kitLineItemPrice->getSubtotal() . self::CURRENCY_USD,
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true,
            ],
            $event->getDataForLineItem($kitLineItem->getEntityIdentifier())
        );
    }

    public function testOnLineItemDataWhenProductKitLineItemHasNoDefinedPrice(): void
    {
        $kitLineItem = $this->getLineItem(1, 10, 'item');
        $lineItems = [
            $kitLineItem->getEntityIdentifier() => $kitLineItem,
        ];

        $kitItem1 = new ProductKitItemStub(100);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(1000))
            ->setKitItem($kitItem1);
        $kitItem2 = new ProductKitItemStub(200);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2000))
            ->setKitItem($kitItem2);
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $kitLineItem->getEntityIdentifier() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [
                        [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1],
                        [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2],
                    ],
                ],
            ],
            $this->createMock(DatagridInterface::class),
            []
        );

        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $this->productLineItemsHolderFactory
            ->expects(self::once())
            ->method('createFromLineItems')
            ->with($lineItems)
            ->willReturn($lineItemsHolder);

        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPricesForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn([]);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1] + self::EMPTY_DATA,
                    [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2] + self::EMPTY_DATA,
                ],
            ] + self::EMPTY_DATA + [DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true],
            $event->getDataForLineItem($kitLineItem->getEntityIdentifier())
        );
    }

    private function getLineItem(int $id, int $quantity, string $unit, ?float $price = null): ProductLineItemInterface
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
