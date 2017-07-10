<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountSubtotalProvider;
use Oro\Bundle\PromotionBundle\Provider\OrdersAppliedDiscountsProvider;
use Oro\Component\Testing\Unit\EntityTrait;

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

    public function testIsSupported()
    {
        $order = new Order();
        $this->setValue($order, 'id', 123);
        $this->assertTrue($this->provider->isSupported($order));
    }

    public function testGetSubtotal()
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 123]);
        $order->setCurrency('USD');

        $this->translator->expects($this->once())->method('trans')->willReturn('test label');
        $this->discountProvider->expects($this->once())
            ->method('getDiscountsAmountByOrder')
            ->with($order)
            ->willReturn(45.67);

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
