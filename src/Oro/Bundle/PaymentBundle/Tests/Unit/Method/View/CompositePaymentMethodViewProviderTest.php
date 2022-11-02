<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

class CompositePaymentMethodViewProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPaymentMethodViews(): void
    {
        $testView = $this->getTypeMock('test_method_view');
        $testView2 = $this->getTypeMock('test_method_view2');

        $viewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $viewProvider
            ->expects(self::any())
            ->method('hasPaymentMethodView')
            ->withConsecutive(['test_method_view'], ['test_method_view2'])
            ->willReturnOnConsecutiveCalls(true, true);

        $viewProvider
            ->expects(self::any())
            ->method('getPaymentMethodView')
            ->withConsecutive(['test_method_view'], ['test_method_view2'])
            ->willReturnOnConsecutiveCalls($testView, $testView2);

        $registry = new CompositePaymentMethodViewProvider([$viewProvider]);

        $views = $registry->getPaymentMethodViews(['test_method_view', 'test_method_view2']);
        self::assertCount(2, $views);
        self::assertEquals([$testView, $testView2], $views);
    }

    public function testGetPaymentMethodView(): void
    {
        $testView = $this->getTypeMock('test_method_view');

        $viewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $viewProvider->expects(self::any())
            ->method('getPaymentMethodView')
            ->with('test_method_view')
            ->willReturn($testView);
        $viewProvider->expects(self::any())
            ->method('hasPaymentMethodView')
            ->with('test_method_view')
            ->willReturn(true);

        $registry = new CompositePaymentMethodViewProvider([$viewProvider]);

        $paymentMethodView = $registry->getPaymentMethodView($testView->getPaymentMethodIdentifier());

        self::assertEquals($paymentMethodView, $testView);
    }

    public function testGetPaymentMethodViewExceptionTriggered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/There is no payment method view for "\w+"/');#z
        $registry = new CompositePaymentMethodViewProvider([]);
        $registry->getPaymentMethodView('not_exists_payment_method');
    }

    private function getTypeMock(string $name): PaymentMethodViewInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $type = $this->createMock(PaymentMethodViewInterface::class);
        $type->expects(self::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($name);

        return $type;
    }
}
