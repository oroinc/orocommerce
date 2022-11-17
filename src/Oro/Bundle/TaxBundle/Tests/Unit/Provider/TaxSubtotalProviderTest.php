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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TaxSubtotalProviderTest extends TestCase
{
    protected TaxSubtotalProvider $provider;

    protected MockObject|TranslatorInterface $translator;

    protected MockObject|TaxProviderInterface $taxProvider;

    protected MockObject|TaxProviderRegistry $taxProviderRegistry;

    protected MockObject|TaxFactory $taxFactory;

    protected MockObject|TaxationSettingsProvider $taxSettingsProvider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())->method('trans')->willReturnCallback(
            function ($message) {
                return ucfirst($message);
            }
        );

        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $this->taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->taxFactory = $this->getMockBuilder(TaxFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxSettingsProvider = $this->getMockBuilder(TaxationSettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new TaxSubtotalProvider(
            $this->translator,
            $this->taxProviderRegistry,
            $this->taxFactory,
            $this->taxSettingsProvider
        );
    }

    protected function tearDown(): void
    {
        unset($this->translator, $this->provider);
    }

    public function testGetSubtotal(): void
    {
        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotal(): void
    {
        $total = $this->createTotalResultElement(150, 'USD');
        $tax   = $this->createTaxResult($total);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, $total);
        $this->assertEquals(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotalEmptyIfTaxationDisabled(): void
    {
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertEmpty($subtotal->getAmount());
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

    public function testIsSupported(): void
    {
        $this->taxFactory->expects($this->once())->method('supports')->willReturn(true);
        $this->assertTrue($this->provider->isSupported(new \stdClass()));
    }

    public function testSupportsCachedSubtotal(): void
    {
        $this->taxFactory->expects($this->once())->method('supports')->willReturn(true);
        $this->assertTrue($this->provider->supportsCachedSubtotal(new \stdClass()));
    }

    protected function assertSubtotal(Subtotal $subtotal, ResultElement $total): void
    {
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(TaxSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals($this->getLabel(), $subtotal->getLabel());
        $this->assertEquals($total->getCurrency(), $subtotal->getCurrency());
        $this->assertEquals($total->getTaxAmount(), $subtotal->getAmount());
        $this->assertEquals($this->getOrder(), $subtotal->getSortOrder());
        $this->assertEquals($this->isVisible(), $subtotal->isVisible());
        $this->assertEquals($this->isRemovable(), $subtotal->isRemovable());
    }

    protected function createTotalResultElement(int $amount, string $currency): ResultElement
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
        $tax->offsetSet(Result::TOTAL, $total);

        return $tax;
    }

    protected function getLabel(): string
    {
        return 'Oro.tax.subtotals.' . TaxSubtotalProvider::TYPE;
    }

    protected function getOrder(): int
    {
        return 500;
    }

    protected function isVisible(): bool
    {
        return true;
    }

    protected function isRemovable(): bool
    {
        return false;
    }
}
