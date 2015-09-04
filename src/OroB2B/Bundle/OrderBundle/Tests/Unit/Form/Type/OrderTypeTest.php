<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class OrderTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderAddressSecurityProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCurrencyHandler = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCurrencyHandler->expects($this->any())
            ->method('setOrderCurrency')
            ->will($this->returnCallback(function ($order) {
                if ($order instanceof Order) {
                    $order->setCurrency('USD');
                }
            }));

        $this->type = new OrderType(
            $this->securityFacade,
            $this->provider,
            $this->paymentTermProvider,
            $this->orderCurrencyHandler
        );
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Order',
                    'intention' => 'order',
                ]
            );

        $this->type->setDataClass('Order');
        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_order_type', $this->type->getName());
    }
}
