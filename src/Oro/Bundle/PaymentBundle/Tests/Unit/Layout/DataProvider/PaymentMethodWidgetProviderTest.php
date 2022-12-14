<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;
use Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodWidgetProvider;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PAYMENT_METHOD_IDENTIFIER = 'payment_method_identifier';
    private const PAYMENT_METHOD_WIDGET = '_payment_method_widget';

    /** @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodViewProvider;

    /** @var PaymentMethodWidgetProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->paymentMethodViewProvider = $this->createMock(CompositePaymentMethodViewProvider::class);

        $this->provider = new PaymentMethodWidgetProvider($this->paymentMethodViewProvider);
    }

    public function testGetPaymentMethodWidgetName()
    {
        $entity = $this->createMock(PaymentMethodAwareInterface::class);
        $entity->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn(self::PAYMENT_METHOD_IDENTIFIER);

        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);
        $paymentMethodView->expects(self::once())
            ->method('getBlock')
            ->willReturn(self::PAYMENT_METHOD_WIDGET);

        $this->paymentMethodViewProvider->expects(self::once())
            ->method('getPaymentMethodView')
            ->with(self::PAYMENT_METHOD_IDENTIFIER)
            ->willReturn($paymentMethodView);

        $prefix = 'test_prefix';

        self::assertSame(
            sprintf('_%s%s', $prefix, self::PAYMENT_METHOD_WIDGET),
            $this->provider->getPaymentMethodWidgetName($entity, $prefix)
        );
    }

    public function testGetPaymentMethodWidgetNameEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Object "stdClass" must implement interface "Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface"'
        );

        $entity = new \stdClass();
        $prefix = 'test_prefix';

        $this->provider->getPaymentMethodWidgetName($entity, $prefix);
    }
}
