<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Provider;

use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderLineItemTaxesAndDiscountsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Provider\OrderLineItemDiscountProvider;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemTaxesAndDiscountsProviderTest extends TestCase
{
    private TaxationSettingsProvider&MockObject $taxationSettingsProvider;
    private TaxProviderInterface&MockObject $taxProvider;
    private OrderLineItemDiscountProvider&MockObject $orderLineItemDiscountProvider;
    private OrderLineItemTaxesAndDiscountsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->orderLineItemDiscountProvider = $this->createMock(OrderLineItemDiscountProvider::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new OrderLineItemTaxesAndDiscountsProvider(
            $this->taxationSettingsProvider,
            $taxProviderRegistry,
            $this->orderLineItemDiscountProvider,
        );
    }

    public function testGetLineItemTaxesReturnsNullWhenTaxationIsDisabled(): void
    {
        $order = new Order();
        $orderLineItem = new OrderLineItem();
        $order->addLineItem($orderLineItem);

        $this->taxationSettingsProvider
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(true);

        $this->taxProvider
            ->expects(self::never())
            ->method(self::anything());

        $result = $this->provider->getLineItemTaxes($orderLineItem);

        self::assertNull($result);
    }

    public function testGetLineItemTaxesReturnsTaxBreakdownWhenTaxationIsEnabled(): void
    {
        $order = new Order();
        $orderLineItem = new OrderLineItem();
        $order->addLineItem($orderLineItem);

        $unitElement = ResultElement::create('11', '10', '1', '0');
        $rowElement = ResultElement::create('55', '50', '5', '0');

        $taxResultElement = TaxResultElement::create('TAX_CODE', '0.1', '50', '5');
        $taxResultElement->offsetSet(AbstractResultElement::CURRENCY, 'USD');

        $lineItemTaxResult = new Result();
        $lineItemTaxResult->offsetSet(Result::UNIT, $unitElement);
        $lineItemTaxResult->offsetSet(Result::ROW, $rowElement);
        $lineItemTaxResult->offsetSet(Result::TAXES, [$taxResultElement]);

        $orderTaxResult = new Result();
        $orderTaxResult->offsetSet(Result::ITEMS, [$lineItemTaxResult]);

        $this->taxationSettingsProvider
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        $this->taxProvider
            ->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn($orderTaxResult);

        $result = $this->provider->getLineItemTaxes($orderLineItem);

        self::assertSame(
            [
                'unit' => $unitElement->getArrayCopy(),
                'row' => $rowElement->getArrayCopy(),
                'taxes' => [$taxResultElement->getArrayCopy()],
            ],
            $result
        );
    }

    public function testGetLineItemTaxesReturnsEmptyTaxesArrayWhenNoTaxes(): void
    {
        $order = new Order();
        $orderLineItem = new OrderLineItem();
        $order->addLineItem($orderLineItem);

        $unitElement = ResultElement::create('0', '0', '0', '0');
        $rowElement = ResultElement::create('0', '0', '0', '0');

        $lineItemTaxResult = new Result();
        $lineItemTaxResult->offsetSet(Result::UNIT, $unitElement);
        $lineItemTaxResult->offsetSet(Result::ROW, $rowElement);
        $lineItemTaxResult->offsetSet(Result::TAXES, []);

        $orderTaxResult = new Result();
        $orderTaxResult->offsetSet(Result::ITEMS, [$lineItemTaxResult]);

        $this->taxationSettingsProvider
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        $this->taxProvider
            ->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn($orderTaxResult);

        $result = $this->provider->getLineItemTaxes($orderLineItem);

        self::assertSame(
            [
                'unit' => $unitElement->getArrayCopy(),
                'row' => $rowElement->getArrayCopy(),
                'taxes' => [],
            ],
            $result
        );
    }

    public function testGetLineItemTaxesMapsMultipleTaxResultElements(): void
    {
        $order = new Order();
        $orderLineItem = new OrderLineItem();
        $order->addLineItem($orderLineItem);

        $unitElement = ResultElement::create('110', '100', '10', '0');
        $rowElement = ResultElement::create('550', '500', '50', '0');

        $taxElement1 = TaxResultElement::create('TAX_1', '0.05', '500', '25');
        $taxElement1->offsetSet(AbstractResultElement::CURRENCY, 'USD');

        $taxElement2 = TaxResultElement::create('TAX_2', '0.05', '500', '25');
        $taxElement2->offsetSet(AbstractResultElement::CURRENCY, 'USD');

        $lineItemTaxResult = new Result();
        $lineItemTaxResult->offsetSet(Result::UNIT, $unitElement);
        $lineItemTaxResult->offsetSet(Result::ROW, $rowElement);
        $lineItemTaxResult->offsetSet(Result::TAXES, [$taxElement1, $taxElement2]);

        $orderTaxResult = new Result();
        $orderTaxResult->offsetSet(Result::ITEMS, [$lineItemTaxResult]);

        $this->taxationSettingsProvider
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        $this->taxProvider
            ->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn($orderTaxResult);

        $result = $this->provider->getLineItemTaxes($orderLineItem);

        self::assertCount(2, $result['taxes']);
        self::assertSame($taxElement1->getArrayCopy(), $result['taxes'][0]);
        self::assertSame($taxElement2->getArrayCopy(), $result['taxes'][1]);
    }

    public function testGetLineItemTaxesReturnsNullWhenLineItemNotInTaxResult(): void
    {
        $order = new Order();
        $orderLineItem = new OrderLineItem();
        $order->addLineItem($orderLineItem);

        $orderTaxResult = new Result();
        $orderTaxResult->offsetSet(Result::ITEMS, []);

        $this->taxationSettingsProvider
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        $this->taxProvider
            ->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn($orderTaxResult);

        $result = $this->provider->getLineItemTaxes($orderLineItem);

        self::assertNull($result);
    }

    public function testGetLineItemTaxesForSecondLineItem(): void
    {
        $order = new Order();
        $orderLineItem1 = new OrderLineItem();
        $orderLineItem2 = new OrderLineItem();
        $order->addLineItem($orderLineItem1);
        $order->addLineItem($orderLineItem2);

        $unitElement1 = ResultElement::create('10', '9', '1', '0');
        $rowElement1 = ResultElement::create('100', '90', '10', '0');

        $unitElement2 = ResultElement::create('20', '18', '2', '0');
        $rowElement2 = ResultElement::create('200', '180', '20', '0');

        $lineItemTaxResult1 = new Result();
        $lineItemTaxResult1->offsetSet(Result::UNIT, $unitElement1);
        $lineItemTaxResult1->offsetSet(Result::ROW, $rowElement1);
        $lineItemTaxResult1->offsetSet(Result::TAXES, []);

        $lineItemTaxResult2 = new Result();
        $lineItemTaxResult2->offsetSet(Result::UNIT, $unitElement2);
        $lineItemTaxResult2->offsetSet(Result::ROW, $rowElement2);
        $lineItemTaxResult2->offsetSet(Result::TAXES, []);

        $orderTaxResult = new Result();
        $orderTaxResult->offsetSet(Result::ITEMS, [$lineItemTaxResult1, $lineItemTaxResult2]);

        $this->taxationSettingsProvider
            ->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        $this->taxProvider
            ->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn($orderTaxResult);

        $result = $this->provider->getLineItemTaxes($orderLineItem2);

        self::assertSame(
            [
                'unit' => $unitElement2->getArrayCopy(),
                'row' => $rowElement2->getArrayCopy(),
                'taxes' => [],
            ],
            $result
        );
    }

    public function testGetLineItemDiscountsDelegatesToDiscountProvider(): void
    {
        $orderLineItem = new OrderLineItem();
        $expectedDiscounts = [
            'appliedDiscountsAmount' => '10.00',
            'rowTotalAfterDiscount' => '90.00',
            'currency' => 'USD',
        ];

        $this->orderLineItemDiscountProvider
            ->expects(self::once())
            ->method('getOrderLineItemDiscount')
            ->with($orderLineItem)
            ->willReturn($expectedDiscounts);

        $result = $this->provider->getLineItemDiscounts($orderLineItem);

        self::assertSame($expectedDiscounts, $result);
    }

    public function testGetLineItemDiscountsReturnsEmptyArrayWhenNoDiscounts(): void
    {
        $orderLineItem = new OrderLineItem();

        $this->orderLineItemDiscountProvider
            ->expects(self::once())
            ->method('getOrderLineItemDiscount')
            ->with($orderLineItem)
            ->willReturn([]);

        $result = $this->provider->getLineItemDiscounts($orderLineItem);

        self::assertSame([], $result);
    }
}
