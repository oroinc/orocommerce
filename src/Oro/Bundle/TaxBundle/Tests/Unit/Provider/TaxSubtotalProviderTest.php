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
    /**
     * @var TaxSubtotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TaxProviderInterface
     */
    protected $taxProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TaxFactory
     */
    protected $taxFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');
        $this->translator->expects($this->any())->method('trans')->willReturnCallback(
            function ($message) {
                return ucfirst($message);
            }
        );

        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->taxFactory = $this->getMockBuilder('Oro\Bundle\TaxBundle\Factory\TaxFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxationSettingsProvider = $this->getMockBuilder(TaxationSettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TaxSubtotalProvider(
            $this->translator,
            $taxProviderRegistry,
            $this->taxFactory,
            $this->taxationSettingsProvider
        );
    }

    protected function tearDown(): void
    {
        unset($this->translator, $this->provider, $this->taxManager);
    }

    public function testGetSubtotal()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResultWithTotal($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals($subtotal->getOperation(), Subtotal::OPERATION_ADD);
    }

    public function testGetSubtotalProductPricesIncludeTax()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);

        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResultWithTotal($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals($subtotal->getOperation(), Subtotal::OPERATION_IGNORE);
    }

    public function testGetCachedSubtotal()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResultWithTotal($total);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals($subtotal->getOperation(), Subtotal::OPERATION_ADD);
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
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Oro.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testIsSupported()
    {
        $this->taxFactory->expects($this->once())->method('supports')->willReturn(true);
        $this->assertTrue($this->provider->isSupported(new \stdClass()));
    }

    public function testSupportsCachedSubtotal()
    {
        $this->taxFactory->expects($this->once())->method('supports')->willReturn(true);
        $this->assertTrue($this->provider->supportsCachedSubtotal(new \stdClass()));
    }

    protected function assertSubtotal(Subtotal $subtotal, ResultElement $total)
    {
        $this->assertInstanceOf('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal', $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('Oro.tax.subtotals.' . TaxSubtotalProvider::TYPE, $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertEquals(500, $subtotal->getSortOrder());
        $this->assertTrue($subtotal->isVisible());
    }

    /**
     * @param int $amount
     * @param string $currency
     * @return ResultElement
     */
    protected function createTotalResultElement($amount, $currency)
    {
        $total = new ResultElement();
        $total
            ->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $amount);

        return $total;
    }

    /**
     * @param ResultElement $total
     * @return Result
     */
    protected function createTaxResultWithTotal(ResultElement $total)
    {
        $tax = new Result();
        $tax->offsetSet(Result::TOTAL, $total);

        return $tax;
    }
}
