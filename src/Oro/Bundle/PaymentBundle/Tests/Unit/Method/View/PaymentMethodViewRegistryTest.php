<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;

class PaymentMethodViewRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodViewRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new PaymentMethodViewRegistry();
    }

    public function testGetPaymentMethodViews()
    {
        $testView = $this->getTypeMock('test_method_view');
        $testView2 = $this->getTypeMock('test_method_view2');

        $this->registry->addPaymentMethodView($testView);
        $this->registry->addPaymentMethodView($testView2);

        $views = $this->registry->getPaymentMethodViews(['test_method_view', 'test_method_view2']);
        $this->assertCount(2, $views);
        $this->assertEquals([$testView, $testView2], $views);
    }

    public function testGetPaymentMethodView()
    {
        $testView = $this->getTypeMock('test_method_view');

        $this->registry->addPaymentMethodView($testView);

        $paymentMethodView = $this->registry->getPaymentMethodView($testView->getPaymentMethodType());

        $this->assertEquals($paymentMethodView, $testView);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp  /There is no payment method view for "\w+"/
     */
    public function testGetPaymentMethodViewExceptionTriggered()
    {
        $this->registry->getPaymentMethodView('not_exists_payment_method');
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentMethodViewInterface
     */
    protected function getTypeMock($name)
    {
        $type = $this->createMock(PaymentMethodViewInterface::class);
        $type->expects($this->any())->method('getPaymentMethodType')->will($this->returnValue($name));

        return $type;
    }
}
