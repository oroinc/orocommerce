<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\Stub\LineItemPriceAwareStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitItemLineItemsDataListener;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\ShoppingListBundle\Model\Factory\ShoppingListLineItemsHolderFactory;
use Oro\Bundle\ShoppingListBundle\Model\ShoppingListLineItemsHolder;
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
        $lineItemsHolderFactory = new ShoppingListLineItemsHolderFactory();
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $numberFormatter = $this->createMock(NumberFormatter::class);

        $this->listener = new DatagridLineItemsDataPricingListener(
            $this->lineItemNotPricedSubtotalProvider,
            $lineItemsHolderFactory,
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
            ->with(new ShoppingListLineItemsHolder(new ArrayCollection($lineItems)))
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
            ->with(new ShoppingListLineItemsHolder(new ArrayCollection($lineItems)))
            ->willReturn($subtotal);

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

        $kitItemLineItem1 = new ProductKitItemLineItemStub(1000);
        $kitItemLineItem2 = new ProductKitItemLineItemStub(2000);
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

        $lineItem1Subtotal = (new Subtotal())->setPrice(Price::create(111.0, self::CURRENCY_USD))->setAmount(1110.0);

        $lineItem2Subtotal = (new Subtotal())->setPrice(Price::create(222.0, self::CURRENCY_USD))->setAmount(12231.234);
        $kitItemLineItem1Subtotal = (new Subtotal())
            ->setPrice(Price::create(1001.1234, self::CURRENCY_USD))
            ->setAmount(1001.1234);
        $kitItemLineItem2Subtotal = (new Subtotal())
            ->setPrice(Price::create(0.0, self::CURRENCY_USD))
            ->setAmount(0.0);
        $lineItem2Subtotal
            ->addLineItemSubtotal($kitItemLineItem1, $kitItemLineItem1Subtotal)
            ->addLineItemSubtotal($kitItemLineItem2, $kitItemLineItem2Subtotal);

        $lineItem3Subtotal = (new Subtotal())->setPrice(Price::create(0.0, self::CURRENCY_USD))->setAmount(0.0);

        $subtotal = new Subtotal();
        $subtotal
            ->setCurrency(self::CURRENCY_USD)
            ->addLineItemSubtotal($lineItem1, $lineItem1Subtotal)
            ->addLineItemSubtotal($lineItem2, $lineItem2Subtotal)
            ->addLineItemSubtotal($lineItem3, $lineItem3Subtotal);

        $this->lineItemNotPricedSubtotalProvider
            ->expects(self::once())
            ->method('getSubtotal')
            ->with(new ShoppingListLineItemsHolder(new ArrayCollection($lineItems)))
            ->willReturn($subtotal);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $lineItem1Subtotal->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $lineItem1Subtotal->getAmount(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $lineItem1Subtotal->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL => $lineItem1Subtotal->getAmount() . self::CURRENCY_USD,
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
                            $kitItemLineItem1Subtotal->getPrice()->getValue(),
                        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitItemLineItem1Subtotal->getAmount(),
                        DatagridLineItemsDataPricingListener::PRICE =>
                            $kitItemLineItem1Subtotal->getPrice()->getValue() . self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL =>
                            $kitItemLineItem1Subtotal->getAmount() . self::CURRENCY_USD,
                    ],
                    [
                        DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2,
                        DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                            $kitItemLineItem2Subtotal->getPrice()->getValue(),
                        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitItemLineItem2Subtotal->getAmount(),
                        DatagridLineItemsDataPricingListener::PRICE =>
                            $kitItemLineItem2Subtotal->getPrice()->getValue() . self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL =>
                            $kitItemLineItem2Subtotal->getAmount() . self::CURRENCY_USD,
                    ],
                ],
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $lineItem2Subtotal->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $lineItem2Subtotal->getAmount(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $lineItem2Subtotal->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL => $lineItem2Subtotal->getAmount() . self::CURRENCY_USD,
            ],
            $event->getDataForLineItem(2)
        );

        self::assertSame(
            [
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $lineItem3Subtotal->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $lineItem3Subtotal->getAmount(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $lineItem3Subtotal->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL => $lineItem3Subtotal->getAmount() . self::CURRENCY_USD,
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
            ->with(new ShoppingListLineItemsHolder(new ArrayCollection($lineItems)))
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

    public function testOnLineItemDataWhenKitItemLineItemHasNoDefinedPrice(): void
    {
        $kitLineItem = $this->getLineItem(1, 10, 'item');
        $lineItems = [
            $kitLineItem->getEntityIdentifier() => $kitLineItem,
        ];

        $kitItemLineItem1 = new ProductKitItemLineItemStub(1000);
        $kitItemLineItem2 = new ProductKitItemLineItemStub(2000);
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

        $kitLineItemSubtotal = (new Subtotal())->setPrice(Price::create(111.0, self::CURRENCY_USD))->setAmount(1110.0);
        $kitItemLineItem1Subtotal = (new Subtotal())
            ->setPrice(Price::create(1001.1234, self::CURRENCY_USD))
            ->setAmount(1001.1234);
        $kitLineItemSubtotal
            ->addLineItemSubtotal($kitItemLineItem1, $kitItemLineItem1Subtotal);

        $subtotal = new Subtotal();
        $subtotal
            ->setCurrency(self::CURRENCY_USD)
            ->addLineItemSubtotal($kitLineItem, $kitLineItemSubtotal);

        $this->lineItemNotPricedSubtotalProvider
            ->expects(self::once())
            ->method('getSubtotal')
            ->with(new ShoppingListLineItemsHolder(new ArrayCollection($lineItems)))
            ->willReturn($subtotal);

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    [
                        DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem1,
                        DatagridLineItemsDataPricingListener::PRICE_VALUE =>
                            $kitItemLineItem1Subtotal->getPrice()->getValue(),
                        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitItemLineItem1Subtotal->getAmount(),
                        DatagridLineItemsDataPricingListener::PRICE =>
                            $kitItemLineItem1Subtotal->getPrice()->getValue() . self::CURRENCY_USD,
                        DatagridLineItemsDataPricingListener::SUBTOTAL =>
                            $kitItemLineItem1Subtotal->getAmount() . self::CURRENCY_USD,
                    ],
                    [DatagridKitItemLineItemsDataListener::ENTITY => $kitItemLineItem2] + self::EMPTY_DATA,
                ],
                DatagridLineItemsDataPricingListener::PRICE_VALUE => $kitLineItemSubtotal->getPrice()->getValue(),
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $kitLineItemSubtotal->getAmount(),
                DatagridLineItemsDataPricingListener::PRICE =>
                    $kitLineItemSubtotal->getPrice()->getValue() . self::CURRENCY_USD,
                DatagridLineItemsDataPricingListener::SUBTOTAL =>
                    $kitLineItemSubtotal->getAmount() . self::CURRENCY_USD,
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true,
            ],
            $event->getDataForLineItem($kitLineItem->getEntityIdentifier())
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
