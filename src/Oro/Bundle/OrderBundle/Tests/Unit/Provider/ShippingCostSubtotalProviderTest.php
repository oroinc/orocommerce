<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\ShippingCostSubtotalProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingCostSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SUBTOTAL_LABEL = 'oro.order.subtotals.shipping_cost (translated)';

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteCurrencyProvider;

    /** @var ShippingCostSubtotalProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('round')
            ->willReturnCallback(function ($value) {
                return round($value, 2);
            });

        $this->provider = new ShippingCostSubtotalProvider(
            $translator,
            $roundingService,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testGetSubtotal(): void
    {
        $currency = 'USD';
        $costAmount = 142.12;

        $order = new Order();
        $order->setCurrency($currency);
        $order->setEstimatedShippingCostAmount($costAmount);

        $subtotal = $this->provider->getSubtotal($order);
        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(ShippingCostSubtotalProvider::TYPE, $subtotal->getType());
        self::assertSame(200, $subtotal->getSortOrder());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertFalse($subtotal->isRemovable());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals($order->getCurrency(), $subtotal->getCurrency());
        self::assertSame($costAmount, $subtotal->getAmount());
    }

    public function testGetSubtotalWhenNoShippingCost(): void
    {
        $order = new Order();
        $order->setCurrency('USD');

        $subtotal = $this->provider->getSubtotal($order);
        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(ShippingCostSubtotalProvider::TYPE, $subtotal->getType());
        self::assertSame(200, $subtotal->getSortOrder());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertFalse($subtotal->isRemovable());
        self::assertFalse($subtotal->isVisible());
        self::assertEquals($order->getCurrency(), $subtotal->getCurrency());
        self::assertSame(0.0, $subtotal->getAmount());
    }

    public function testGetSubtotalDemandQuote(): void
    {
        $costAmount = 143.55;
        $currency = 'EUR';

        $quote = new Quote();
        $quote->setEstimatedShippingCostAmount($costAmount);
        $quote->setCurrency($currency);
        $quoteDemand = new QuoteDemand();
        $quoteDemand->setQuote($quote);

        $subtotal = $this->provider->getSubtotal($quoteDemand);
        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(ShippingCostSubtotalProvider::TYPE, $subtotal->getType());
        self::assertSame(200, $subtotal->getSortOrder());
        self::assertEquals(self::SUBTOTAL_LABEL, $subtotal->getLabel());
        self::assertFalse($subtotal->isRemovable());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals($currency, $subtotal->getCurrency());
        self::assertSame($costAmount, $subtotal->getAmount());
    }
}
