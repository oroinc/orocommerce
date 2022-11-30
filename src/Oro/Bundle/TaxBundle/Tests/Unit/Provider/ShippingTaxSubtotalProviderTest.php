<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\ShippingTaxSubtotalProvider;
use Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider;

class ShippingTaxSubtotalProviderTest extends TaxSubtotalProviderTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new ShippingTaxSubtotalProvider(
            $this->translator,
            $this->taxProviderRegistry,
            $this->taxFactory,
            $this->taxSettingsProvider
        );
    }

    public function testGetSubtotal(): void
    {
        $this->taxSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(false);

        $total = $this->createTotalResultElement(100, 'USD');
        $tax = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalShippingRatesIncludeTax(): void
    {
        $this->taxSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(true);

        $total = $this->createTotalResultElement(100, 'USD');
        $tax = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotal(): void
    {
        $this->taxSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(false);

        $total = $this->createTotalResultElement(100, 'USD');
        $tax = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    protected function createTotalResultElement($amount, $currency): ResultElement
    {
        $total = new ResultElement();
        $total
            ->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $amount);

        return $total;
    }

    protected function createTaxResult(ResultElement $total): Result
    {
        $tax = new Result();
        $tax->offsetSet(Result::SHIPPING, $total);

        return $tax;
    }

    protected function getLabel(): string
    {
        return 'Oro.tax.subtotals.shipping_' . TaxSubtotalProvider::TYPE;
    }

    protected function getOrder(): int
    {
        return 420;
    }

    protected function isVisible(): bool
    {
        return false;
    }

    protected function isRemovable(): bool
    {
        return true;
    }
}
