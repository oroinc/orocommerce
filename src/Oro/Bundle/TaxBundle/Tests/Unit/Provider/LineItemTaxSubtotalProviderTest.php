<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\LineItemTaxSubtotalProvider;
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
final class LineItemTaxSubtotalProviderTest extends TestCase
{
    private const SUBTOTAL_LABEL = 'Oro.tax.subtotals.lineitem_tax';

    private TaxProviderInterface&MockObject $taxProvider;

    private TaxFactory&MockObject $taxFactory;

    private TaxationSettingsProvider&MockObject $taxSettingsProvider;

    private TaxManager&MockObject $taxManager;

    private LineItemTaxSubtotalProvider $provider;

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
        $this->taxManager = $this->createMock(TaxManager::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects(self::any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new LineItemTaxSubtotalProvider(
            $translator,
            $taxProviderRegistry,
            $this->taxFactory,
            $this->taxSettingsProvider,
            $this->taxManager,
        );
    }

    public function testGetSubtotal(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willReturn($this->createTaxResult('10', 'USD'));

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, 10.0, 'USD');
        self::assertSame(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalProductPricesIncludeTax(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willReturn($this->createTaxResult('10', 'USD'));

        $subtotal = $this->provider->getSubtotal(new Order());

        $this->assertSubtotal($subtotal, 10.0, 'USD');
        self::assertSame(Subtotal::OPERATION_IGNORE, $subtotal->getOperation());
    }

    public function testGetCachedSubtotal(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $this->taxProvider->expects(self::once())
            ->method('loadTax')
            ->willReturn($this->createTaxResult('10', 'USD'));

        // The result already carries ITEMS, so no on-demand loading is triggered.
        $this->taxManager->expects(self::never())
            ->method('loadTax');

        $subtotal = $this->provider->getCachedSubtotal(new Order());

        $this->assertSubtotal($subtotal, 10.0, 'USD');
        self::assertSame(Subtotal::OPERATION_ADD, $subtotal->getOperation());
    }

    public function testGetSubtotalByResultLoadsTaxItemsWhenMissing(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $itemsTotal = new ResultElement();
        $itemsTotal->setCurrency('USD')
            ->offsetSet(ResultElement::TAX_AMOUNT, '10');

        $tax = new Result();
        $tax->offsetSet(Result::ITEMS_TOTAL, $itemsTotal);

        $lineItem = new OrderLineItem();
        $order = new Order();
        $order->addLineItem($lineItem);

        $lineItemTax = new Result();

        $this->taxProvider->expects(self::never())
            ->method('getTax');
        $this->taxProvider->expects(self::never())
            ->method('loadTax');
        // ITEMS are absent for the order, so they are loaded on demand.
        $this->taxManager->expects(self::once())
            ->method('loadTax')
            ->with($lineItem)
            ->willReturn($lineItemTax);

        $subtotal = $this->provider->getSubtotalByResult($tax, $order);

        $this->assertSubtotal($subtotal, 10.0, 'USD');
        self::assertSame([$lineItemTax], $tax->offsetGet(Result::ITEMS));
    }

    public function testGetSubtotalByResultSkipsTaxItemsWhenPresent(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $this->taxManager->expects(self::never())
            ->method('loadTax');

        $subtotal = $this->provider->getSubtotalByResult($this->createTaxResult('10', 'USD'), new Order());

        $this->assertSubtotal($subtotal, 10.0, 'USD');
    }

    public function testGetSubtotalUsesItemsTotalNotSumOfRoundedTaxes(): void
    {
        $this->taxSettingsProvider->expects(self::once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        /**
         * Product 1 (VAT 10%): exact tax = 0.346, rounded = 0.35
         * Product 2 (VAT 22%): exact tax = 0.3388, rounded = 0.34
         * sum(rounded) = 0.69  - wrong behaviour
         * round(sum)   = 0.68  - correct: ITEMS_TOTAL stores the pre-rounding sum
         **/

        $itemsTotal = new ResultElement();
        $itemsTotal->setCurrency('EUR')
            ->offsetSet(ResultElement::TAX_AMOUNT, '0.68');

        $taxElement1 = new ResultElement();
        $taxElement1->setCurrency('EUR')
            ->offsetSet(ResultElement::TAX_AMOUNT, '0.35');

        $taxElement2 = new ResultElement();
        $taxElement2->setCurrency('EUR')
            ->offsetSet(ResultElement::TAX_AMOUNT, '0.34');

        $tax = new Result();
        $tax->offsetSet(Result::ITEMS_TOTAL, $itemsTotal);
        $tax->offsetSet(Result::TAXES, [$taxElement1, $taxElement2]);
        $tax->offsetSet(Result::ITEMS, []);

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->willReturn($tax);

        $subtotal = $this->provider->getSubtotal(new Order());

        self::assertSame(0.68, $subtotal->getAmount());
        self::assertSame('EUR', $subtotal->getCurrency());
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
        self::assertSame(LineItemTaxSubtotalProvider::NAME, $subtotal->getName());
        self::assertSame(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertSame($expectedCurrency, $subtotal->getCurrency());
        self::assertSame($expectedAmount, $subtotal->getAmount());
        self::assertSame(410, $subtotal->getSortOrder());
        self::assertFalse($subtotal->isVisible());
        self::assertTrue($subtotal->isRemovable());
    }

    private function createTaxResult(string $taxAmount, string $currency): Result
    {
        $itemsTotal = new ResultElement();
        $itemsTotal->setCurrency($currency)
            ->offsetSet(ResultElement::TAX_AMOUNT, $taxAmount);

        $rowTax = new Result();
        $rowTax->offsetSet(Result::ROW, $itemsTotal);

        $tax = new Result();
        $tax->offsetSet(Result::ITEMS, [$rowTax]);
        $tax->offsetSet(Result::TAXES, [$itemsTotal]);
        $tax->offsetSet(Result::ITEMS_TOTAL, $itemsTotal);

        return $tax;
    }
}
