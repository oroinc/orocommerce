<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodViewsProvider;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class PaymentMethodViewsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /**
     * @var CompositePaymentMethodViewProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewProvider;

    /**
     * @var PaymentMethodProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodProvider;

    /**
     * @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var PaymentMethodViewsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentMethodViewProvider = $this
            ->getMockBuilder(CompositePaymentMethodViewProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodProvider = $this
            ->getMockBuilder(PaymentMethodProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodProvider = $this->createMock(PaymentMethodProvider::class);

        $this->paymentTransactionProvider = $this->getMockBuilder(PaymentTransactionProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->provider = new PaymentMethodViewsProvider(
            $this->paymentMethodViewProvider,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider
        );
    }

    public function testGetViewsEmpty()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([]);

        $this->paymentMethodViewProvider->expects(static::never())
            ->method('getPaymentMethodViews');

        $data = $this->provider->getViews($context);
        $this->assertEmpty($data);
    }

    public function testGetViews()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $methodType = 'payment_method';

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getIdentifier')
            ->willReturn($methodType);

        $this->paymentMethodProvider->expects(static::once())
            ->method('getApplicablePaymentMethods')
            ->with($context)
            ->willReturn([$paymentMethod]);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $view->expects($this->once())->method('getLabel')->willReturn('label');
        $view->expects($this->once())->method('getBlock')->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($context)
            ->willReturn([]);
        $view->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($methodType);

        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodViews')
            ->with([$methodType])
            ->willReturn([$view]);

        $data = $this->provider->getViews($context);
        $this->assertEquals([$methodType => ['label' => 'label', 'block' => 'block', 'options' => []]], $data);
    }

    public function testGetPaymentMethods()
    {
        $entity = new \stdClass();
        $this->paymentTransactionProvider->expects($this->once())->method('getPaymentMethods')->with($entity);
        $this->provider->getPaymentMethods($entity);
    }
}
