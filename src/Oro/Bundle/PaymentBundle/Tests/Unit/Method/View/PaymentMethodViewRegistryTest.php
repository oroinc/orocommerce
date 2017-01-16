<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;

class PaymentMethodViewProvidersRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodViewProvidersRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new PaymentMethodViewProvidersRegistry();
    }

    public function testGetPaymentMethodViews()
    {
        $testView = $this->getTypeMock('test_method_view');
        $testView2 = $this->getTypeMock('test_method_view2');

        $viewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $viewProvider->expects($this->any())->method('getPaymentMethodViews')
            ->with(['test_method_view', 'test_method_view2'])
            ->will($this->returnValue([$testView, $testView2]));

        $this->registry->addProvider($viewProvider);

        $views = $this->registry->getPaymentMethodViews(['test_method_view', 'test_method_view2']);
        $this->assertCount(2, $views);
        $this->assertEquals([$testView, $testView2], $views);
    }

    public function testGetPaymentMethodView()
    {
        $testView = $this->getTypeMock('test_method_view');

        $viewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $viewProvider->expects($this->any())->method('getPaymentMethodView')
            ->with('test_method_view')
            ->will($this->returnValue($testView));
        $viewProvider->expects($this->any())->method('hasPaymentMethodView')
            ->with('test_method_view')
            ->will($this->returnValue(true));

        $this->registry->addProvider($viewProvider);

        $paymentMethodView = $this->registry->getPaymentMethodView($testView->getPaymentMethodIdentifier());

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
        $type->expects($this->any())->method('getPaymentMethodIdentifier')->will($this->returnValue($name));

        return $type;
    }
}
