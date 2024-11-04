<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\LineItemTaxSubtotalProvider;
use Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LineItemTaxSubtotalProviderTest extends TaxSubtotalProviderTest
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $taxManager = $this->createMock(TaxManager::class);

        $this->provider = new LineItemTaxSubtotalProvider(
            $this->translator,
            $this->taxProviderRegistry,
            $this->taxFactory,
            $this->taxSettingsProvider,
            $taxManager
        );
    }

    #[\Override]
    public function testGetSubtotal(): void
    {
        $this->taxSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $total = $this->createTotalResultElement(10, 'USD');
        $tax = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalProductPricesIncludeTax(): void
    {
        $this->taxSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);

        $total = $this->createTotalResultElement(10, 'USD');
        $tax = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    #[\Override]
    public function testGetCachedSubtotal(): void
    {
        $this->taxSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $total = $this->createTotalResultElement(10, 'USD');
        $tax = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    #[\Override]
    protected function createTotalResultElement($amount, $currency): ResultElement
    {
        $total = new ResultElement();
        $total
            ->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $amount);

        return $total;
    }

    #[\Override]
    protected function createTaxResult(ResultElement $total): Result
    {
        $rowTax = new Result();
        $rowTax->offsetSet(Result::ROW, $total);

        $tax = new Result();
        $tax->offsetSet(Result::ITEMS, [$rowTax]);
        $tax->offsetSet(Result::TAXES, [$total]);

        return $tax;
    }

    #[\Override]
    protected function getLabel(): string
    {
        return 'Oro.tax.subtotals.lineitem_' . TaxSubtotalProvider::TYPE;
    }

    #[\Override]
    protected function getOrder(): int
    {
        return 410;
    }

    #[\Override]
    protected function isVisible(): bool
    {
        return false;
    }

    #[\Override]
    protected function isRemovable(): bool
    {
        return true;
    }
}
