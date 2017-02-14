<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;
use Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodWidgetProvider;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodWidgetProviderTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_IDENTIFIER = 'payment_method_identifier';
    const PAYMENT_METHOD_WIDGET = '_payment_method_widget';

    /**
     * @var CompositePaymentMethodViewProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewProvider;

    /**
     * @var PaymentMethodWidgetProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->paymentMethodViewProvider = $this
            ->getMockBuilder(CompositePaymentMethodViewProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PaymentMethodWidgetProvider($this->paymentMethodViewProvider);
    }

    public function testGetPaymentMethodWidgetName()
    {
        $entity = $this->createMock(PaymentMethodAwareInterface::class);
        $entity->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn(self::PAYMENT_METHOD_IDENTIFIER);

        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);
        $paymentMethodView->expects(static::once())
            ->method('getBlock')
            ->willReturn(self::PAYMENT_METHOD_WIDGET);

        $this->paymentMethodViewProvider->expects(static::once())
            ->method('getPaymentMethodView')
            ->with(self::PAYMENT_METHOD_IDENTIFIER)
            ->willReturn($paymentMethodView);


        $prefix = 'test_prefix';

        static::assertSame(
            sprintf('_%s%s', $prefix, self::PAYMENT_METHOD_WIDGET),
            $this->provider->getPaymentMethodWidgetName($entity, $prefix)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object "stdClass" must implement interface "Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface"
     */
    // @codingStandardsIgnoreEnd
    public function testGetPaymentMethodWidgetNameEmpty()
    {
        $entity = new \stdClass();
        $prefix = 'test_prefix';

        $this->provider->getPaymentMethodWidgetName($entity, $prefix);
    }
}
