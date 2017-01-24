<?php

namespace Oro\Bundle\AlternativeBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider\PaymentTermViewProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
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
     * @var PaymentMethodProvidersRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodRegistry;

    /**
     * @var PaymentTermViewProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentMethodViewRegistry = $this->createMock(PaymentMethodViewProvidersRegistry::class);

        $this->paymentMethodRegistry = $this->createMock(PaymentMethodProvidersRegistryInterface::class);

        $this->provider = new PaymentTermViewProvider(
            $this->paymentMethodViewRegistry,
            $this->paymentMethodRegistry
        );
    }

    public function testGetViewNotApplicable()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

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
        $context = $this->createMock(PaymentContextInterface::class);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

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
        $context = $this->createMock(PaymentContextInterface::class);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

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
