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

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TaxSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProvider;

    /** @var TaxProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProviderRegistry;

    /** @var TaxFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $taxFactory;

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxSettingsProvider;

    /** @var TaxSubtotalProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($message) => ucfirst($message));

        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxFactory = $this->createMock(TaxFactory::class);
        $this->taxSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $this->taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new TaxSubtotalProvider(
            $this->translator,
            $this->taxProviderRegistry,
            $this->taxFactory,
            $this->taxSettingsProvider
        );
    }

    public function testGetSubtotal(): void
    {
        $total = $this->createTotalResultElement(150, 'USD');

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($this->createTaxResult($total));

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotal(): void
    {
        $total = $this->createTotalResultElement(150, 'USD');

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($this->createTaxResult($total));

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetSubtotalByResultDoesNotResolveTax(): void
    {
        $total = $this->createTotalResultElement(150, 'USD');

        $this->taxProvider->expects($this->never())
            ->method('getTax');
        $this->taxProvider->expects($this->never())
            ->method('loadTax');

        $subtotal = $this->provider->getSubtotalByResult($this->createTaxResult($total), new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetSubtotalWithException(): void
    {
        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals($this->getLabel(), $subtotal->getLabel());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testGetCachedSubtotalEmptyIfTaxationDisabled(): void
    {
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertEmpty($subtotal->getAmount());
    }

    public function testIsSupported(): void
    {
        $this->taxFactory->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $this->assertTrue($this->provider->isSupported(new \stdClass()));
    }

    public function testSupportsCachedSubtotal(): void
    {
        $this->taxFactory->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $this->assertTrue($this->provider->supportsCachedSubtotal(new \stdClass()));
    }

    private function assertSubtotal(Subtotal $subtotal, ResultElement $total): void
    {
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals(TaxSubtotalProvider::NAME, $subtotal->getName());
        $this->assertEquals($this->getLabel(), $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertEquals(500, $subtotal->getSortOrder());
        $this->assertTrue($subtotal->isVisible());
        $this->assertFalse($subtotal->isRemovable());
    }

    private function createTotalResultElement(int $amount, string $currency): ResultElement
    {
        $total = new ResultElement();
        $total->setCurrency($currency)->offsetSet(ResultElement::TAX_AMOUNT, $amount);

        return $total;
    }

    private function createTaxResult(ResultElement $total): Result
    {
        $tax = new Result();
        $tax->offsetSet(Result::TOTAL, $total);

        return $tax;
    }

    private function getLabel(): string
    {
        return 'Oro.tax.subtotals.' . TaxSubtotalProvider::TYPE;
    }
}
