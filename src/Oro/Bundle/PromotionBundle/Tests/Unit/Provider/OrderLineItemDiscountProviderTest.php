<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\PromotionBundle\Provider\OrderLineItemDiscountProvider;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDiscountProviderTest extends TestCase
{
    private TaxationSettingsProvider&MockObject $taxationSettingsProvider;
    private TaxProviderRegistry&MockObject $taxProviderRegistry;
    private LineItemSubtotalProvider&MockObject $lineItemSubtotalProvider;
    private AppliedDiscountsProvider&MockObject $appliedDiscountsProvider;
    private OrderLineItemDiscountProvider $provider;

    protected function setUp(): void
    {
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->appliedDiscountsProvider = $this->createMock(AppliedDiscountsProvider::class);

        $this->provider = new OrderLineItemDiscountProvider(
            $this->taxationSettingsProvider,
            $this->taxProviderRegistry,
            $this->lineItemSubtotalProvider,
            $this->appliedDiscountsProvider
        );
    }

    public function testGetOrderLineItemDiscountWithoutTaxes()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('USD');

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(10.0);

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($lineItem, 'USD')
            ->willReturn(100.0);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('10', $result['appliedDiscountsAmount']);
        $this->assertSame('90', $result['rowTotalAfterDiscount']);
        $this->assertSame('USD', $result['currency']);
        $this->assertArrayNotHasKey('rowTotalAfterDiscountExcludingTax', $result);
        $this->assertArrayNotHasKey('rowTotalAfterDiscountIncludingTax', $result);
    }

    public function testGetOrderLineItemDiscountWithoutTaxesAndZeroDiscount()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('EUR');

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(0.0);

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($lineItem, 'EUR')
            ->willReturn(50.0);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('0', $result['appliedDiscountsAmount']);
        $this->assertSame('50', $result['rowTotalAfterDiscount']);
        $this->assertSame('EUR', $result['currency']);
    }

    public function testGetOrderLineItemDiscountWithTaxesAndDiscountsNotIncluded()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('USD');

        $taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxResult = new Result();
        $rowElement = ResultElement::create('110.0', '100.0', '10.0', '0.0');
        $rowElement->setDiscountsIncluded(false);
        $taxResult->offsetSet(Result::ROW, $rowElement);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($taxProvider);

        $taxProvider->expects($this->once())
            ->method('getTax')
            ->with($lineItem)
            ->willReturn($taxResult);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(15.0);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('15', $result['appliedDiscountsAmount']);
        $this->assertSame('85.0', $result['rowTotalAfterDiscountExcludingTax']);
        $this->assertSame('95.0', $result['rowTotalAfterDiscountIncludingTax']);
        $this->assertSame('USD', $result['currency']);
        $this->assertArrayNotHasKey('rowTotalAfterDiscount', $result);
    }

    public function testGetOrderLineItemDiscountWithTaxesAndDiscountsIncluded()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('EUR');

        $taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxResult = new Result();
        $rowElement = ResultElement::create('220.0', '200.0', '20.0', '0.0');
        $rowElement->setDiscountsIncluded(true);
        $taxResult->offsetSet(Result::ROW, $rowElement);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($taxProvider);

        $taxProvider->expects($this->once())
            ->method('getTax')
            ->with($lineItem)
            ->willReturn($taxResult);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(25.0);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('25', $result['appliedDiscountsAmount']);
        $this->assertSame('200.0', $result['rowTotalAfterDiscountExcludingTax']);
        $this->assertSame('220.0', $result['rowTotalAfterDiscountIncludingTax']);
        $this->assertSame('EUR', $result['currency']);
    }

    public function testGetOrderLineItemDiscountWithTaxesAndZeroDiscount()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('CAD');

        $taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxResult = new Result();
        $rowElement = ResultElement::create('330.0', '300.0', '30.0', '0.0');
        $rowElement->setDiscountsIncluded(false);
        $taxResult->offsetSet(Result::ROW, $rowElement);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($taxProvider);

        $taxProvider->expects($this->once())
            ->method('getTax')
            ->with($lineItem)
            ->willReturn($taxResult);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(0.0);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('0', $result['appliedDiscountsAmount']);
        $this->assertSame('300.0', $result['rowTotalAfterDiscountExcludingTax']);
        $this->assertSame('330.0', $result['rowTotalAfterDiscountIncludingTax']);
        $this->assertSame('CAD', $result['currency']);
    }

    public function testGetOrderLineItemDiscountWithTaxesAndDecimalValues()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('USD');

        $taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxResult = new Result();
        $rowElement = ResultElement::create('123.45', '111.11', '12.34', '0.0');
        $rowElement->setDiscountsIncluded(false);
        $taxResult->offsetSet(Result::ROW, $rowElement);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($taxProvider);

        $taxProvider->expects($this->once())
            ->method('getTax')
            ->with($lineItem)
            ->willReturn($taxResult);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(11.11);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('11.11', $result['appliedDiscountsAmount']);
        $this->assertSame('100.00', $result['rowTotalAfterDiscountExcludingTax']);
        $this->assertSame('112.34', $result['rowTotalAfterDiscountIncludingTax']);
        $this->assertSame('USD', $result['currency']);
    }

    public function testGetOrderLineItemDiscountWithoutTaxesAndDecimalValues()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('EUR');

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->appliedDiscountsProvider->expects($this->once())
            ->method('getDiscountsAmountByLineItem')
            ->with($lineItem)
            ->willReturn(33.33);

        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getRowTotal')
            ->with($lineItem, 'EUR')
            ->willReturn(99.99);

        $result = $this->provider->getOrderLineItemDiscount($lineItem);

        $this->assertSame('33.33', $result['appliedDiscountsAmount']);
        $this->assertSame('66.66', $result['rowTotalAfterDiscount']);
        $this->assertSame('EUR', $result['currency']);
    }
}
