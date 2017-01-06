<?php

namespace Oro\Bundle\AlternativeBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider\PaymentTermViewProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Component\Testing\Unit\EntityTrait;

class PaymentTermViewProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = PaymentTerm::TYPE;

    /**
     * @var PaymentMethodViewProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentMethodProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodRegistry;

    /**
     * @var PaymentTermViewProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentMethodViewRegistry = $this
            ->getMockBuilder(PaymentMethodViewProvidersRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodRegistry = $this
            ->getMockBuilder(PaymentMethodProvidersRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PaymentTermViewProvider(
            $this->paymentMethodViewRegistry,
            $this->paymentMethodRegistry
        );
    }

    public function testGetViewNotApplicable()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->getMockBuilder(PaymentContextInterface::class)->getMock();

        $paymentMethod = $this->getMockBuilder(PaymentMethodInterface::class)->getMock();

        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);
        
        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethodProvider')
            ->with(static::METHOD)
            ->willReturn($paymentMethodProvider);

        $paymentMethod->expects(static::once())
            ->method('isApplicable')
            ->willReturn(false);

        $this->assertNull($this->provider->getView($context));
    }

    public function testGetViewException()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->getMockBuilder(PaymentContextInterface::class)->getMock();

        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethodProvider')
            ->with(static::METHOD)
            ->willReturn($paymentMethodProvider);

        $this->assertNull($this->provider->getView($context));
    }

    public function testGetView()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->getMockBuilder(PaymentContextInterface::class)->getMock();

        $paymentMethod = $this->getMockBuilder(PaymentMethodInterface::class)->getMock();

        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethodProvider')
            ->with(static::METHOD)
            ->willReturn($paymentMethodProvider);

        $paymentMethod->expects(static::once())
            ->method('isApplicable')
            ->willReturn(true);

        $paymentMethod->expects(static::once())
            ->method('getIdentifier')
            ->willReturn(static::METHOD);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $view->expects($this->once())->method('getPaymentMethodIdentifier')->willReturn(static::METHOD);
        $view->expects($this->once())->method('getLabel')->willReturn('label');
        $view->expects($this->once())->method('getBlock')->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($context)
            ->willReturn([]);

        $this->paymentMethodViewRegistry->expects(static::once())
            ->method('getPaymentMethodViews')
            ->with([static::METHOD])
            ->willReturn([$view]);

        $data = $this->provider->getView($context);
        $this->assertEquals([static::METHOD => ['label' => 'label', 'block' => 'block', 'options' => []]], $data);
    }
}
