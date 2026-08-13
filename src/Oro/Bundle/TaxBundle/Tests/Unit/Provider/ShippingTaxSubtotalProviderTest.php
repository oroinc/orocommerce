<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\ShippingTaxSubtotalProvider;
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
final class ShippingTaxSubtotalProviderTest extends TestCase
{
    private const SUBTOTAL_LABEL = 'Oro.tax.subtotals.shipping_tax';

    private TaxProviderInterface&MockObject $taxProvider;

    private TaxFactory&MockObject $taxFactory;

    private TaxationSettingsProvider&MockObject $taxSettingsProvider;

    private ShippingTaxSubtotalProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => ucfirst($id));

        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxFactory = $this->createMock(TaxFactory::class);
        $this->taxSettingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects(self::any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new ShippingTaxSubtotalProvider(
            $translator,
            $taxProviderRegistry,
            $this->taxFactory,
            $this->taxSettingsProvider,
        );
    }

    public function testGetSubtotal(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(false);

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willReturn($this->createTaxResult('100', 'USD'));

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, 100.0, 'USD');
        self::assertSame(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalShippingRatesIncludeTax(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(true);

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willReturn($this->createTaxResult('100', 'USD'));

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, 100.0, 'USD');
        self::assertSame(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotal(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(false);

        $this->taxProvider->expects(self::once())
            ->method('loadTax')
            ->willReturn($this->createTaxResult('100', 'USD'));

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, 100.0, 'USD');
        self::assertSame(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalByResultDoesNotResolveTax(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(false);

        $this->taxProvider->expects(self::never())
            ->method('getTax');
        $this->taxProvider->expects(self::never())
            ->method('loadTax');

        $subtotal = $this->provider->getSubtotalByResult($this->createTaxResult('100', 'USD'), new Order());

        $this->assertSubtotal($subtotal, 100.0, 'USD');
        self::assertSame(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalWithException(): void
    {
        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getSubtotal(new Order());

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertSame(TaxSubtotalProvider::TYPE, $subtotal->getType());
        self::assertSame(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertFalse($subtotal->isVisible());
    }

    public function testGetCachedSubtotalEmptyIfTaxationDisabled(): void
    {
        $this->taxProvider->expects(self::once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        self::assertSame(0.0, $subtotal->getAmount());
    }

    public function testIsSupported(): void
    {
        $this->taxFactory->expects(self::once())
            ->method('supports')
            ->willReturn(true);

        self::assertTrue($this->provider->isSupported(new \stdClass()));
    }

    public function testSupportsCachedSubtotal(): void
    {
        $this->taxFactory->expects(self::once())
            ->method('supports')
            ->willReturn(true);

        self::assertTrue($this->provider->supportsCachedSubtotal(new \stdClass()));
    }

    private function assertSubtotal(Subtotal $subtotal, float $expectedAmount, string $expectedCurrency): void
    {
        self::assertSame(TaxSubtotalProvider::TYPE, $subtotal->getType());
        self::assertSame(ShippingTaxSubtotalProvider::NAME, $subtotal->getName());
        self::assertSame(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertSame($expectedCurrency, $subtotal->getCurrency());
        self::assertSame($expectedAmount, $subtotal->getAmount());
        self::assertSame(420, $subtotal->getSortOrder());
        self::assertFalse($subtotal->isVisible());
        self::assertTrue($subtotal->isRemovable());
    }

    private function createTaxResult(string $taxAmount, string $currency): Result
    {
        $shipping = new ResultElement();
        $shipping->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $taxAmount);

        $tax = new Result();
        $tax->offsetSet(Result::SHIPPING, $shipping);

        return $tax;
    }
}
