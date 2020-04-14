<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

class CompositePaymentMethodViewProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPaymentMethodViews()
    {
        $testView = $this->getTypeMock('test_method_view');
        $testView2 = $this->getTypeMock('test_method_view2');

        /** @var PaymentMethodViewProviderInterface|\PHPUnit\Framework\MockObject\MockObject $viewProvider */
        $viewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $viewProvider->expects($this->any())->method('getPaymentMethodViews')
            ->with(['test_method_view', 'test_method_view2'])
            ->will($this->returnValue([$testView, $testView2]));

        $registry = new CompositePaymentMethodViewProvider([$viewProvider]);

        $views = $registry->getPaymentMethodViews(['test_method_view', 'test_method_view2']);
        $this->assertCount(2, $views);
        $this->assertEquals([$testView, $testView2], $views);
    }

    public function testGetPaymentMethodView()
    {
        $testView = $this->getTypeMock('test_method_view');

        /** @var PaymentMethodViewProviderInterface|\PHPUnit\Framework\MockObject\MockObject $viewProvider */
        $viewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $viewProvider->expects($this->any())->method('getPaymentMethodView')
            ->with('test_method_view')
            ->will($this->returnValue($testView));
        $viewProvider->expects($this->any())->method('hasPaymentMethodView')
            ->with('test_method_view')
            ->will($this->returnValue(true));

        $registry = new CompositePaymentMethodViewProvider([$viewProvider]);

        $paymentMethodView = $registry->getPaymentMethodView($testView->getPaymentMethodIdentifier());

        $this->assertEquals($paymentMethodView, $testView);
    }

    public function testGetPaymentMethodViewExceptionTriggered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/There is no payment method view for "\w+"/');#z
        $registry = new CompositePaymentMethodViewProvider([]);
        $registry->getPaymentMethodView('not_exists_payment_method');
    }

    /**
     * @param string $name
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|PaymentMethodViewInterface
     */
    protected function getTypeMock($name)
    {
        $type = $this->createMock(PaymentMethodViewInterface::class);
        $type->expects($this->any())->method('getPaymentMethodIdentifier')->will($this->returnValue($name));

        return $type;
    }
}
