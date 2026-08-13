<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\LineItemTaxSubtotalProvider;
use Oro\Bundle\TaxBundle\Provider\ShippingTaxSubtotalProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider;
use Oro\Bundle\TaxBundle\Provider\TaxSubtotalProviderComposite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class TaxSubtotalProviderCompositeTest extends TestCase
{
    private TaxProviderInterface&MockObject $taxProvider;

    private TaxFactory&MockObject $taxFactory;

    private TaxSubtotalProvider&MockObject $taxSubtotalProvider;

    private ShippingTaxSubtotalProvider&MockObject $shippingTaxSubtotalProvider;

    private LineItemTaxSubtotalProvider&MockObject $lineItemTaxSubtotalProvider;

    private TaxSubtotalProviderComposite $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxFactory = $this->createMock(TaxFactory::class);
        $this->taxSubtotalProvider = $this->createMock(TaxSubtotalProvider::class);
        $this->shippingTaxSubtotalProvider = $this->createMock(ShippingTaxSubtotalProvider::class);
        $this->lineItemTaxSubtotalProvider = $this->createMock(LineItemTaxSubtotalProvider::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects(self::any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new TaxSubtotalProviderComposite(
            $taxProviderRegistry,
            $this->taxFactory,
            $this->taxSubtotalProvider,
            $this->shippingTaxSubtotalProvider,
            $this->lineItemTaxSubtotalProvider,
        );
    }

    public function testGetSubtotalResolvesTaxResultOnceAndDelegates(): void
    {
        $entity = new Order();
        $tax = new Result();

        // The tax result must be resolved only once for all three tax subtotals.
        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->with($entity)
            ->willReturn($tax);

        $taxSubtotal = (new Subtotal())->setName(TaxSubtotalProvider::NAME);
        $shippingSubtotal = (new Subtotal())->setName(ShippingTaxSubtotalProvider::NAME);
        $lineItemSubtotal = (new Subtotal())->setName(LineItemTaxSubtotalProvider::NAME);

        $this->taxSubtotalProvider->expects(self::once())
            ->method('getSubtotalByResult')
            ->with($tax, $entity)
            ->willReturn($taxSubtotal);
        $this->shippingTaxSubtotalProvider->expects(self::once())
            ->method('getSubtotalByResult')
            ->with($tax, $entity)
            ->willReturn($shippingSubtotal);
        $this->lineItemTaxSubtotalProvider->expects(self::once())
            ->method('getSubtotalByResult')
            ->with($tax, $entity)
            ->willReturn($lineItemSubtotal);

        $subtotals = $this->provider->getSubtotal($entity);

        self::assertSame([$taxSubtotal, $shippingSubtotal, $lineItemSubtotal], $subtotals);
    }

    public function testGetCachedSubtotalResolvesTaxResultOnceAndDelegates(): void
    {
        $entity = new Order();
        $tax = new Result();

        $this->taxProvider->expects(self::once())
            ->method('loadTax')
            ->with($entity)
            ->willReturn($tax);

        $taxSubtotal = (new Subtotal())->setName(TaxSubtotalProvider::NAME);
        $shippingSubtotal = (new Subtotal())->setName(ShippingTaxSubtotalProvider::NAME);
        $lineItemSubtotal = (new Subtotal())->setName(LineItemTaxSubtotalProvider::NAME);

        $this->taxSubtotalProvider->expects(self::once())
            ->method('getSubtotalByResult')
            ->with($tax, $entity)
            ->willReturn($taxSubtotal);
        $this->shippingTaxSubtotalProvider->expects(self::once())
            ->method('getSubtotalByResult')
            ->with($tax, $entity)
            ->willReturn($shippingSubtotal);
        $this->lineItemTaxSubtotalProvider->expects(self::once())
            ->method('getSubtotalByResult')
            ->with($tax, $entity)
            ->willReturn($lineItemSubtotal);

        $subtotals = $this->provider->getCachedSubtotal($entity);

        self::assertSame([$taxSubtotal, $shippingSubtotal, $lineItemSubtotal], $subtotals);
    }

    public function testGetSubtotalReturnsEmptySubtotalsWhenTaxationDisabled(): void
    {
        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willThrowException(new TaxationDisabledException());

        $taxEmpty = (new Subtotal())->setName(TaxSubtotalProvider::NAME);
        $shippingEmpty = (new Subtotal())->setName(ShippingTaxSubtotalProvider::NAME);
        $lineItemEmpty = (new Subtotal())->setName(LineItemTaxSubtotalProvider::NAME);

        $this->taxSubtotalProvider->expects(self::once())
            ->method('createEmptySubtotal')
            ->willReturn($taxEmpty);
        $this->shippingTaxSubtotalProvider->expects(self::once())
            ->method('createEmptySubtotal')
            ->willReturn($shippingEmpty);
        $this->lineItemTaxSubtotalProvider->expects(self::once())
            ->method('createEmptySubtotal')
            ->willReturn($lineItemEmpty);

        $this->taxSubtotalProvider->expects(self::never())
            ->method('getSubtotalByResult');
        $this->shippingTaxSubtotalProvider->expects(self::never())
            ->method('getSubtotalByResult');
        $this->lineItemTaxSubtotalProvider->expects(self::never())
            ->method('getSubtotalByResult');

        $subtotals = $this->provider->getSubtotal(new Order());

        self::assertSame([$taxEmpty, $shippingEmpty, $lineItemEmpty], $subtotals);
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
}
