<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodViewRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodViewRegistry */
    protected $registry;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMockBuilder('Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = new PaymentMethodViewRegistry($this->paymentMethodRegistry);
    }

    public function testRegistry()
    {
        $testView = $this->getTypeMock('test_method_view', 10);
        $testView2 = $this->getTypeMock('test_method_view2', 5);

        $testViewMethodDisabled = $this->getTypeMock('test_method_view_disabled');

        $this->assertEmpty($this->registry->getPaymentMethodViews());

        $this->registry->addPaymentMethodView($testView);
        $this->registry->addPaymentMethodView($testView2);
        $this->registry->addPaymentMethodView($testViewMethodDisabled);

        $paymentMethod = $this->getMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->exactly(3))->method('isEnabled')
            ->willReturnOnConsecutiveCalls(true, true, false);
        $paymentMethod->expects($this->exactly(2))->method('isApplicable')->willReturnOnConsecutiveCalls(true, false);
        $this->paymentMethodRegistry->expects($this->exactly(3))->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $views = $this->registry->getPaymentMethodViews();
        $this->assertCount(1, $views);
        $this->assertSame($testView, reset($views));
    }

    public function testGetPaymentMethodViewsWithCorrectOrder()
    {
        $testView = $this->getTypeMock('test_method_view', 10);
        $testView2 = $this->getTypeMock('test_method_view2', 20);

        $this->registry->addPaymentMethodView($testView);
        $this->registry->addPaymentMethodView($testView2);

        $paymentMethod = $this->getMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');

        $paymentMethod
            ->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $paymentMethod
            ->expects($this->exactly(2))
            ->method('isApplicable')
            ->willReturn(true);

        $this->paymentMethodRegistry
            ->expects($this->exactly(2))
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $views = $this->registry->getPaymentMethodViews();
        $this->assertCount(2, $views);
        $this->assertSame($testView, reset($views));
        $this->assertSame($testView2, end($views));
    }

    public function testGetPaymentMethodViewsWithSameOrder()
    {
        $testView = $this->getTypeMock('test_method_view', 10);
        $testView2 = $this->getTypeMock('test_method_view2', 10);

        $this->registry->addPaymentMethodView($testView);
        $this->registry->addPaymentMethodView($testView2);

        $paymentMethod = $this->getMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');

        $paymentMethod
            ->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $paymentMethod
            ->expects($this->exactly(2))
            ->method('isApplicable')
            ->willReturn(true);

        $this->paymentMethodRegistry
            ->expects($this->exactly(2))
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $views = $this->registry->getPaymentMethodViews();
        $this->assertCount(2, $views);
        $this->assertEquals([$testView, $testView2], $views);
    }

    public function testGetPaymentMethodView()
    {
        $testView = $this->getTypeMock('test_method_view', 10);

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
     * @param int $order
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentMethodViewInterface
     */
    protected function getTypeMock($name, $order = 0)
    {
        $type = $this->getMock('Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $type->expects($this->any())->method('getPaymentMethodType')->will($this->returnValue($name));
        $type->expects($this->any())->method('getOrder')->will($this->returnValue($order));

        return $type;
    }
}
