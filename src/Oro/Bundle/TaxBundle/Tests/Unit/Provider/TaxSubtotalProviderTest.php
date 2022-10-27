<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaxSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxProviderInterface */
    private $taxProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxFactory */
    private $taxFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxationSettingsProvider */
    private $taxationSettingsProvider;

    /** @var TaxSubtotalProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxFactory = $this->createMock(TaxFactory::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($message) {
                return ucfirst($message);
            });

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new TaxSubtotalProvider(
            $translator,
            $taxProviderRegistry,
            $this->taxFactory,
            $this->taxationSettingsProvider
        );
    }

    public function testGetSubtotal()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $total = $this->getTotalResultElement(150, 'USD');
        $tax = $this->getTaxResultWithTotal($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalProductPricesIncludeTax()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);

        $total = $this->getTotalResultElement(150, 'USD');
        $tax = $this->getTaxResultWithTotal($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotal()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $total = $this->getTotalResultElement(150, 'USD');
        $tax = $this->getTaxResultWithTotal($total);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetCachedSubtotalEmptyIfTaxationDisabled()
    {
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertEmpty($subtotal->getAmount());
    }

    public function testGetSubtotalWithException()
    {
        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getSubtotal(new Order());
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Oro.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testIsSupported()
    {
        $this->taxFactory->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $this->assertTrue($this->provider->isSupported(new \stdClass()));
    }

    public function testSupportsCachedSubtotal()
    {
        $this->taxFactory->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $this->assertTrue($this->provider->supportsCachedSubtotal(new \stdClass()));
    }

    private function assertSubtotal(Subtotal $subtotal, ResultElement $total)
    {
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Oro.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertEquals(500, $subtotal->getSortOrder());
        $this->assertTrue($subtotal->isVisible());
    }

    private function getTotalResultElement(int $amount, string $currency): ResultElement
    {
        $total = new ResultElement();
        $total
            ->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $amount);

        return $total;
    }

    private function getTaxResultWithTotal(ResultElement $total): Result
    {
        $tax = new Result();
        $tax->offsetSet(Result::TOTAL, $total);

        return $tax;
    }
}
