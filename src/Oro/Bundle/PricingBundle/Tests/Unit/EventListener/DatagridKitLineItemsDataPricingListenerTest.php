<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridKitLineItemsDataPricingListener;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener;
use Oro\Bundle\PricingBundle\Tests\Unit\Stub\LineItemPriceAwareStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridKitLineItemsDataPricingListenerTest extends TestCase
{
    private const CURRENCY_USD = 'USD';
    private const EMPTY_DATA = [
        DatagridLineItemsDataPricingListener::PRICE_VALUE => null,
        DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => null,
        DatagridLineItemsDataPricingListener::PRICE => null,
        DatagridLineItemsDataPricingListener::SUBTOTAL => null,
    ];

    private NumberFormatter|MockObject $numberFormatter;

    private DatagridKitLineItemsDataPricingListener $listener;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->numberFormatter
            ->method('formatCurrency')
            ->willReturnCallback(static fn ($value, $currency) => $value . $currency);

        $this->listener = new DatagridKitLineItemsDataPricingListener($this->numberFormatter);
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

    public function testOnLineItemDataWhenNotKit(): void
    {
        $lineItem = $this->getLineItem(10, 1, 'item');
        $lineItemsData = [$lineItem->getEntityIdentifier() => []];
        $event = new DatagridLineItemsDataEvent(
            [$lineItem->getEntityIdentifier() => $lineItem],
            $lineItemsData,
            $this->createMock(Datagrid::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertSame($lineItemsData, $event->getDataForAllLineItems());
    }

    public function testOnLineItemDataWhenNoCurrency(): void
    {
        $lineItem = $this->getLineItem(10, 1, 'item');
        $lineItemsData = [$lineItem->getEntityIdentifier() => [DatagridKitLineItemsDataListener::IS_KIT => true]];
        $event = new DatagridLineItemsDataEvent(
            [$lineItem->getEntityIdentifier() => $lineItem],
            $lineItemsData,
            $this->createMock(Datagrid::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertSame($lineItemsData, $event->getDataForAllLineItems());
    }

    public function testOnLineItemDataWhenNoKitItemLineItems(): void
    {
        $lineItem = $this->getLineItem(10, 1, 'item');
        $lineItemsData = [
            $lineItem->getEntityIdentifier() => [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
            ]
        ];
        $event = new DatagridLineItemsDataEvent(
            [$lineItem->getEntityIdentifier() => $lineItem],
            $lineItemsData,
            $this->createMock(Datagrid::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                $lineItem->getEntityIdentifier() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                    DatagridLineItemsDataPricingListener::PRICE_VALUE => 0.0,
                    DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 0.0,
                    DatagridLineItemsDataPricingListener::PRICE => '0USD',
                    DatagridLineItemsDataPricingListener::SUBTOTAL => '0USD',
                ]
            ],
            $event->getDataForAllLineItems()
        );
    }

    public function testOnLineItemDataWhenNoKitItemLineItemSubtotal(): void
    {
        $lineItemId = 10;
        $lineItem = $this->getLineItem($lineItemId, 1, 'item');
        $lineItemsData = [
            $lineItemId => [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 100.0,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    1010 => []
                ],
            ]
        ];
        $event = new DatagridLineItemsDataEvent(
            [$lineItemId => $lineItem],
            $lineItemsData,
            $this->createMock(Datagrid::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                $lineItemId => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridLineItemsDataPricingListener::PRICE_VALUE => null,
                    DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                    DatagridKitLineItemsDataListener::SUB_DATA =>
                        $lineItemsData[$lineItemId][DatagridKitLineItemsDataListener::SUB_DATA],
                    DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => null,
                    DatagridLineItemsDataPricingListener::PRICE => null,
                    DatagridLineItemsDataPricingListener::SUBTOTAL => null,
                ]
            ],
            $event->getDataForAllLineItems()
        );
    }

    public function testOnLineItemDataWhenHasKitItemLineItemWithSubtotal(): void
    {
        $lineItemId = 10;
        $lineItem = $this->getLineItem($lineItemId, 3, 'item');
        $lineItemsData = [
            $lineItemId => [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridLineItemsDataPricingListener::PRICE_VALUE => 100.111,
                DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    1010 => [
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 11.123,
                    ],
                    2020 => [
                        DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 22.345,
                    ],
                ],
            ]
        ];
        $event = new DatagridLineItemsDataEvent(
            [$lineItemId => $lineItem],
            $lineItemsData,
            $this->createMock(Datagrid::class),
            []
        );

        $this->listener->onLineItemData($event);

        self::assertSame(
            [
                $lineItemId => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridLineItemsDataPricingListener::PRICE_VALUE => 133.579,
                    DatagridLineItemsDataPricingListener::CURRENCY => self::CURRENCY_USD,
                    DatagridKitLineItemsDataListener::SUB_DATA =>
                        $lineItemsData[$lineItemId][DatagridKitLineItemsDataListener::SUB_DATA],
                    DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 400.737,
                    DatagridLineItemsDataPricingListener::PRICE => '133.579USD',
                    DatagridLineItemsDataPricingListener::SUBTOTAL => '400.737USD',
                ]
            ],
            $event->getDataForAllLineItems()
        );
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();

        foreach ($lineItems as $lineItem) {
            $lineItemData = $event->getDataForLineItem($lineItem->getEntityIdentifier());
            if (empty($lineItemData[DatagridKitLineItemsDataListener::IS_KIT])) {
                continue;
            }

            $currency = $lineItemData[DatagridLineItemsDataPricingListener::CURRENCY];
            if (empty($currency)) {
                continue;
            }

            $priceValue = (float)$lineItemData[DatagridLineItemsDataPricingListener::PRICE_VALUE];
            foreach ($lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] as $kitItemLineItemData) {
                if (empty($kitItemLineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE])) {
                    $event->addDataForLineItem(
                        $lineItem->getEntityIdentifier(),
                        [
                            DatagridLineItemsDataPricingListener::PRICE_VALUE => 0.0,
                            DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => 0.0,
                            DatagridLineItemsDataPricingListener::PRICE => '',
                            DatagridLineItemsDataPricingListener::SUBTOTAL => '',
                        ]
                    );
                    continue 2;
                }

                $priceValue = $this->sumValuesAsBigDecimal(
                    $priceValue,
                    (float)$kitItemLineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE]
                );
            }

            $subtotalValue = $priceValue * (float)$lineItem->getQuantity();

            $event->addDataForLineItem(
                $lineItem->getEntityIdentifier(),
                [
                    'priceValue' => $priceValue,
                    'subtotalValue' => $priceValue * (float)$lineItem->getQuantity(),
                    'price' => $this->numberFormatter->formatCurrency($priceValue, $currency),
                    'subtotal' => $this->numberFormatter->formatCurrency($subtotalValue, $currency),
                ]
            );
        }
    }

    private function getLineItem(int $id, int $quantity, string $unit, float $price = null): ProductLineItemInterface
    {
        $product = (new ProductStub())->setId($id * 10);
        $productUnit = (new ProductUnit())->setCode($unit);

        if ($price) {
            $price = Price::create($price, self::CURRENCY_USD);
        }

        return (new LineItemPriceAwareStub())
            ->setId($id)
            ->setProduct($product)
            ->setQuantity($quantity)
            ->setProductUnit($productUnit)
            ->setPrice($price);
    }
}
