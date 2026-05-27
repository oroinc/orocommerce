<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\OrderLineItemDraftIsPriceOverriddenDatagridListener;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Pricing\OrderLineItemIsPriceOverriddenCalculator;
use Oro\Bundle\OrderBundle\Provider\OrderLineItemTierPricesProvider;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftIsPriceOverriddenDatagridListenerTest extends TestCase
{
    private OrderLineItemTierPricesProvider&MockObject $tierPricesProvider;
    private OrderLineItemIsPriceOverriddenCalculator&MockObject $isPriceOverriddenCalculator;
    private OrderLineItemDraftIsPriceOverriddenDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->tierPricesProvider = $this->createMock(OrderLineItemTierPricesProvider::class);
        $this->isPriceOverriddenCalculator = $this->createMock(OrderLineItemIsPriceOverriddenCalculator::class);
        $this->listener = new OrderLineItemDraftIsPriceOverriddenDatagridListener(
            $this->tierPricesProvider,
            $this->isPriceOverriddenCalculator,
        );
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $this->tierPricesProvider
            ->expects(self::never())
            ->method('getTierPricesForLineItems');

        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), []);

        $this->listener->onResultAfter($event);

        self::assertSame([], $event->getRecords());
    }

    public function testOnResultAfterWhenRootEntityIsInvalid(): void
    {
        $this->tierPricesProvider
            ->expects(self::never())
            ->method('getTierPricesForLineItems');

        $record = new ResultRecord(['priceValue' => '10.0000', 'productUnitCode' => 'item', 'quantity' => 2]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertSame([], $record->getValue('tierPrices'));
        self::assertFalse($record->getValue('isPriceOverridden'));
    }

    public function testOnResultAfterWhenLineItemHasNoProduct(): void
    {
        $this->tierPricesProvider
            ->expects(self::never())
            ->method('getTierPricesForLineItems');

        $lineItem = new OrderLineItem();
        $record = new ResultRecord([
            $lineItem,
            'priceValue' => '10.0000',
            'productUnitCode' => 'item',
            'quantity' => 2,
        ]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertSame([], $record->getValue('tierPrices'));
        self::assertFalse($record->getValue('isPriceOverridden'));
    }

    public function testOnResultAfterSetsTierPricesAndDelegatesToCalculator(): void
    {
        $product = $this->createProduct(42);
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $tierPrice = new ProductPriceDTO($product, Price::create(10.0, 'USD'), 1.0, $this->createUnit('item'));

        $this->tierPricesProvider
            ->expects(self::once())
            ->method('getTierPricesForLineItems')
            ->with(self::callback(static fn (array $items) => \in_array($lineItem, $items, true)))
            ->willReturnCallback(static function (array $items) use ($tierPrice) {
                $result = [];
                foreach ($items as $key => $item) {
                    $result[$key] = [$tierPrice];
                }

                return $result;
            });

        $this->isPriceOverriddenCalculator
            ->expects(self::once())
            ->method('isOverridden')
            ->with(self::identicalTo($lineItem), [$tierPrice])
            ->willReturn(false);

        $record = new ResultRecord([
            $lineItem,
            'priceValue' => '10.0000',
            'productUnitCode' => 'item',
            'quantity' => 5,
        ]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertSame(
            [
                [
                    'price' => 10.0,
                    'currency' => 'USD',
                    'quantity' => 1.0,
                    'unit' => 'item',
                ],
            ],
            $record->getValue('tierPrices')
        );
        self::assertFalse($record->getValue('isPriceOverridden'));
    }

    public function testOnResultAfterWhenCalculatorReturnsTrueSetsPriceOverridden(): void
    {
        $product = $this->createProduct(42);
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $tierPrice = new ProductPriceDTO($product, Price::create(8.0, 'USD'), 1.0, $this->createUnit('item'));

        $this->tierPricesProvider
            ->expects(self::once())
            ->method('getTierPricesForLineItems')
            ->willReturnCallback(static function (array $items) use ($tierPrice) {
                $result = [];
                foreach ($items as $key => $item) {
                    $result[$key] = [$tierPrice];
                }

                return $result;
            });

        $this->isPriceOverriddenCalculator
            ->expects(self::once())
            ->method('isOverridden')
            ->willReturn(true);

        $record = new ResultRecord([$lineItem, 'priceValue' => '7.5000', 'productUnitCode' => 'item', 'quantity' => 7]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertTrue($record->getValue('isPriceOverridden'));
    }

    public function testOnResultAfterWhenNoTierPricesForLineItem(): void
    {
        $product = $this->createProduct(42);
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $this->tierPricesProvider
            ->expects(self::once())
            ->method('getTierPricesForLineItems')
            ->willReturnCallback(static function (array $items) {
                return array_fill_keys(array_keys($items), []);
            });

        $this->isPriceOverriddenCalculator
            ->expects(self::once())
            ->method('isOverridden')
            ->with(self::identicalTo($lineItem), [])
            ->willReturn(false);

        $record = new ResultRecord([$lineItem, 'priceValue' => '9.5000', 'productUnitCode' => 'item', 'quantity' => 5]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertSame([], $record->getValue('tierPrices'));
        self::assertFalse($record->getValue('isPriceOverridden'));
    }

    public function testOnResultAfterBatchesMultipleLineItemsInSingleProviderCall(): void
    {
        $product1 = $this->createProduct(1);
        $product2 = $this->createProduct(2);

        $lineItem1 = new OrderLineItem();
        $lineItem1->setProduct($product1);

        $lineItem2 = new OrderLineItem();
        $lineItem2->setProduct($product2);

        $tierPrice1 = new ProductPriceDTO($product1, Price::create(5.0, 'USD'), 1.0, $this->createUnit('item'));
        $tierPrice2 = new ProductPriceDTO($product2, Price::create(15.0, 'USD'), 1.0, $this->createUnit('set'));

        $this->tierPricesProvider
            ->expects(self::once())
            ->method('getTierPricesForLineItems')
            ->with(self::callback(static fn (array $items) => \count($items) === 2))
            ->willReturnCallback(
                static function (array $items) use ($lineItem1, $lineItem2, $tierPrice1, $tierPrice2) {
                    $result = [];
                    foreach ($items as $key => $item) {
                        $result[$key] = $item === $lineItem1 ? [$tierPrice1] : [$tierPrice2];
                    }

                    return $result;
                }
            );

        $this->isPriceOverriddenCalculator
            ->expects(self::exactly(2))
            ->method('isOverridden')
            ->willReturn(false);

        $record1 = new ResultRecord([$lineItem1, 'priceValue' => '5.0000', 'quantity' => 1]);
        $record2 = new ResultRecord([$lineItem2, 'priceValue' => '15.0000', 'quantity' => 1]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record1, $record2]);

        $this->listener->onResultAfter($event);

        self::assertSame(
            [['price' => 5.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'item']],
            $record1->getValue('tierPrices')
        );
        self::assertSame(
            [['price' => 15.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'set']],
            $record2->getValue('tierPrices')
        );
    }

    public function testOnResultAfterMixedRecordsValidAndInvalid(): void
    {
        $product = $this->createProduct(42);
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $tierPrice = new ProductPriceDTO($product, Price::create(10.0, 'USD'), 1.0, $this->createUnit('item'));

        $this->tierPricesProvider
            ->expects(self::once())
            ->method('getTierPricesForLineItems')
            ->with(self::callback(static fn (array $items) => \count($items) === 1))
            ->willReturnCallback(static function (array $items) use ($tierPrice) {
                $result = [];
                foreach ($items as $key => $item) {
                    $result[$key] = [$tierPrice];
                }

                return $result;
            });

        $this->isPriceOverriddenCalculator
            ->expects(self::once())
            ->method('isOverridden')
            ->willReturn(false);

        // Invalid record (no root entity)
        $invalidRecord = new ResultRecord(['priceValue' => '5.0000']);
        // Valid record
        $validRecord = new ResultRecord([$lineItem, 'priceValue' => '10.0000', 'quantity' => 1]);

        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$invalidRecord, $validRecord]);

        $this->listener->onResultAfter($event);

        self::assertSame([], $invalidRecord->getValue('tierPrices'));
        self::assertFalse($invalidRecord->getValue('isPriceOverridden'));
        self::assertSame(
            [['price' => 10.0, 'currency' => 'USD', 'quantity' => 1.0, 'unit' => 'item']],
            $validRecord->getValue('tierPrices')
        );
    }

    private function createProduct(int $id): ProductStub
    {
        $product = new ProductStub();
        $product->setId($id);

        return $product;
    }

    private function createUnit(string $code): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }
}
