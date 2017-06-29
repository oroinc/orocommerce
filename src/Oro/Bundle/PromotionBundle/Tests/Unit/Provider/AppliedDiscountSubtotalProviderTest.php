<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountSubtotalProvider;
use Oro\Bundle\PromotionBundle\Provider\OrdersAppliedDiscountsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class AppliedDiscountSubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OrdersAppliedDiscountsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $discountProvider;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var AppliedDiscountSubtotalProvider */
    protected $provider;

    public function setUp()
    {
        $this->discountProvider = $this->createMock(OrdersAppliedDiscountsProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new AppliedDiscountSubtotalProvider($this->discountProvider, $this->translator);
    }

    public function testIsSupportedFail()
    {
        $this->discountProvider->expects($this->once())->method('getOrderDiscounts')->willReturn([]);
        $this->assertFalse($this->provider->isSupported(new \stdClass()));

        $order = new Order();
        $this->setValue($order, 'id', 123);
        $this->assertFalse($this->provider->isSupported($order));
    }

    public function testIsSupported()
    {
        $this->discountProvider->expects($this->once())
            ->method('getOrderDiscounts')
            ->willReturn([new AppliedDiscount(), new AppliedDiscount()]);

        $order = new Order();
        $this->setValue($order, 'id', 123);
        $this->assertTrue($this->provider->isSupported($order));
    }

    public function testGetSubtotal()
    {
        $orderId = 123;
        $this->translator->expects($this->once())->method('trans')->willReturn('test label');
        $this->discountProvider->expects($this->once())
            ->method('getOrderDiscountAmount')
            ->with($orderId)
            ->willReturn(45.67);

        $order = new Order();
        $order->setCurrency('USD');
        $this->setValue($order, 'id', $orderId);

        $expectedSubtotal = new Subtotal();
        $expectedSubtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);
        $expectedSubtotal->setType(AppliedDiscountSubtotalProvider::TYPE);
        $expectedSubtotal->setLabel('test label');
        $expectedSubtotal->setVisible(true);
        $expectedSubtotal->setCurrency('USD');
        $expectedSubtotal->setAmount(45.67);

        $this->assertEquals($expectedSubtotal, $this->provider->getSubtotal($order));

    }
}
