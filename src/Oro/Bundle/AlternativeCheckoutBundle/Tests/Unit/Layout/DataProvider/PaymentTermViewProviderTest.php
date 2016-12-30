<?php

namespace Oro\Bundle\AlternativeBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider\PaymentTermViewProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Component\Testing\Unit\EntityTrait;

class PaymentTermViewProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = PaymentTerm::TYPE;

    /**
     * @var PaymentMethodViewRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodRegistry;

    /**
     * @var PaymentTermViewProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentMethodViewRegistry = $this
            ->getMockBuilder(PaymentMethodViewRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodRegistry = $this
            ->getMockBuilder(PaymentMethodRegistry::class)
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

        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethod')
            ->with(static::METHOD)
            ->willReturn($paymentMethod);
        $paymentMethod->expects(static::once())
            ->method('isApplicable')
            ->willReturn(false);

        $this->assertNull($this->provider->getView($context));
    }

    public function testGetViewException()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->getMockBuilder(PaymentContextInterface::class)->getMock();
        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethod')
            ->with(static::METHOD)
            ->willThrowException(new \InvalidArgumentException());

        $this->assertNull($this->provider->getView($context));
    }

    public function testGetView()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->getMockBuilder(PaymentContextInterface::class)->getMock();

        $paymentMethod = $this->getMockBuilder(PaymentMethodInterface::class)->getMock();

        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethod')
            ->with(static::METHOD)
            ->willReturn($paymentMethod);
        $paymentMethod->expects(static::once())
            ->method('isApplicable')
            ->willReturn(true);

        $view = $this->createMock(PaymentMethodViewInterface::class);
        $view->expects($this->once())->method('getLabel')->willReturn('label');
        $view->expects($this->once())->method('getBlock')->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($context)
            ->willReturn([]);

        $this->paymentMethodViewRegistry->expects(static::once())
            ->method('getPaymentMethodView')
            ->with(static::METHOD)
            ->willReturn($view);

        $data = $this->provider->getView($context);
        $this->assertEquals(['label' => 'label', 'block' => 'block', 'options' => []], $data);
    }
}
