<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DiscountSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LineItemSubtotalProvider */
    private $lineItemSubtotal;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    private $tokenAccessor;

    /** @var DiscountSubtotalProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->lineItemSubtotal = $this->createMock(LineItemSubtotalProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($value) {
                return round($value, 2, PHP_ROUND_HALF_UP);
            });

        $this->provider = new DiscountSubtotalProvider(
            $this->translator,
            $roundingService,
            $this->lineItemSubtotal,
            $this->tokenAccessor,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider)
        );
    }

    public function testGetDiscountSubtotalEmpty()
    {
        $this->translator->expects($this->never())
            ->method('trans')
            ->with('oro.order.subtotals.' . DiscountSubtotalProvider::TYPE)
            ->willReturn(ucfirst(DiscountSubtotalProvider::TYPE));

        $order = new Order();
        $currency = 'USD';
        $order->setCurrency($currency);

        $subtotal = $this->provider->getSubtotal($order);
        $this->assertEmpty($subtotal);
    }

    public function testGetDiscountSubtotal()
    {
        $this->translator->expects($this->exactly(3))
            ->method('trans')
            ->with('oro.order.subtotals.' . DiscountSubtotalProvider::TYPE)
            ->willReturn(ucfirst(DiscountSubtotalProvider::TYPE));
        $subtotalMock =  $this->createMock(Subtotal::class);
        $this->lineItemSubtotal->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotalMock);
        $subtotalMock->expects($this->once())
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
        [$firstDiscountSubtotal, $secondDiscountSubtotal, $threadDiscountSubtotal] = $subtotal;
        $this->assertCount(3, $subtotal);
        $this->assertInstanceOf(Subtotal::class, $firstDiscountSubtotal);
        $this->assertEquals(DiscountSubtotalProvider::TYPE, $firstDiscountSubtotal->getType());
        $this->assertEquals($description . ' (Discount)', $firstDiscountSubtotal->getLabel());
        $this->assertEquals('Discount', $secondDiscountSubtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $firstDiscountSubtotal->getCurrency());
        $this->assertIsFloat($firstDiscountSubtotal->getAmount());
        $this->assertEquals(150, $firstDiscountSubtotal->getAmount());
        $this->assertEquals(100, $secondDiscountSubtotal->getAmount());
        $this->assertEquals(100, $threadDiscountSubtotal->getAmount());
    }

    public function testGetDiscountSubtotalFrontendUser()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.order.subtotals.' . DiscountSubtotalProvider::TYPE)
            ->willReturn(ucfirst(DiscountSubtotalProvider::TYPE));

        $customerUser = $this->createMock(CustomerUser::class);
        $this->tokenAccessor->expects($this->once())
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
        $discountSubtotal = $subtotal[0];
        $this->assertCount(1, $subtotal);
        $this->assertInstanceOf(Subtotal::class, $discountSubtotal);
        $this->assertEquals(DiscountSubtotalProvider::TYPE, $discountSubtotal->getType());
        $this->assertEquals($description, $discountSubtotal->getLabel());
        $this->assertEquals($order->getCurrency(), $discountSubtotal->getCurrency());
        $this->assertEquals(50, $discountSubtotal->getSortOrder());
        $this->assertIsFloat($discountSubtotal->getAmount());
        $this->assertEquals(150, $discountSubtotal->getAmount());
    }

    public function testIsSupportedEntity()
    {
        $order = new Order();
        $supported = $this->provider->isSupported($order);
        $this->assertTrue($supported);
    }

    public function testIsNotSupportedEntity()
    {
        $order = new \stdClass();
        $supported = $this->provider->isSupported($order);
        $this->assertFalse($supported);
    }
}
