<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DiscountSubtotalProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SUBTOTAL_LABEL = 'oro.order.subtotals.discount (translated)';

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteCurrencyProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemSubtotal;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var DiscountSubtotalProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->lineItemSubtotal = $this->createMock(LineItemSubtotalProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

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
                return round($value, 2, PHP_ROUND_HALF_UP);
            });

        $this->provider = new DiscountSubtotalProvider(
            $translator,
            $roundingService,
            $this->lineItemSubtotal,
            $this->tokenAccessor,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testIsSupportedEntity(): void
    {
        self::assertTrue($this->provider->isSupported(new Order()));
    }

    public function testIsNotSupportedEntity(): void
    {
        self::assertFalse($this->provider->isSupported(new \stdClass()));
    }

    public function testGetDiscountSubtotalEmpty(): void
    {
        $order = new Order();
        $currency = 'USD';
        $order->setCurrency($currency);

        self::assertSame([], $this->provider->getSubtotal($order));
    }

    public function testGetDiscountSubtotal(): void
    {
        $subtotalMock =  $this->createMock(Subtotal::class);
        $this->lineItemSubtotal->expects(self::once())
            ->method('getSubtotal')
            ->willReturn($subtotalMock);
        $subtotalMock->expects(self::once())
            ->method('getAmount')
            ->willReturn(1000.0);

        $order = new Order();
        $discount1 = new OrderDiscount();
        $discount1->setAmount(150);
        $description = 'test';
        $discount1->setDescription($description);
        $discount2 = new OrderDiscount();
        $discount2->setAmount(100);
        $discount3 = new OrderDiscount();
        $discount3->setPercent(10);
        $discount3->setType(OrderDiscount::TYPE_PERCENT);
        $currency = 'USD';
        $order->setCurrency($currency);
        $order->addDiscount($discount1);
        $order->addDiscount($discount2);
        $order->addDiscount($discount3);

        $subtotal = $this->provider->getSubtotal($order);
        self::assertCount(3, $subtotal);
        [$firstDiscountSubtotal, $secondDiscountSubtotal, $threadDiscountSubtotal] = $subtotal;
        self::assertInstanceOf(Subtotal::class, $firstDiscountSubtotal);
        self::assertEquals(DiscountSubtotalProvider::TYPE, $firstDiscountSubtotal->getType());
        self::assertEquals($description . ' (' . self::SUBTOTAL_LABEL . ')', $firstDiscountSubtotal->getLabel());
        self::assertEquals(self::SUBTOTAL_LABEL, $secondDiscountSubtotal->getLabel());
        self::assertEquals($order->getCurrency(), $firstDiscountSubtotal->getCurrency());
        self::assertSame(150.0, $firstDiscountSubtotal->getAmount());
        self::assertSame(100.0, $secondDiscountSubtotal->getAmount());
        self::assertSame(100.0, $threadDiscountSubtotal->getAmount());
    }

    public function testGetDiscountSubtotalFrontendUser(): void
    {
        $customerUser = $this->createMock(CustomerUser::class);
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $order = new Order();
        $discount = new OrderDiscount();
        $discount->setAmount(150);
        $description = 'test';
        $discount->setDescription($description);
        $currency = 'USD';
        $order->setCurrency($currency);
        $order->addDiscount($discount);

        $subtotal = $this->provider->getSubtotal($order);
        self::assertCount(1, $subtotal);
        $discountSubtotal = $subtotal[0];
        self::assertInstanceOf(Subtotal::class, $discountSubtotal);
        self::assertEquals(DiscountSubtotalProvider::TYPE, $discountSubtotal->getType());
        self::assertEquals($description, $discountSubtotal->getLabel());
        self::assertEquals($order->getCurrency(), $discountSubtotal->getCurrency());
        self::assertSame(50, $discountSubtotal->getSortOrder());
        self::assertSame(150.0, $discountSubtotal->getAmount());
    }
}
