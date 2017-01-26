<?php

namespace Oro\Bundle\AlternativeBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider\PaymentTermViewProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
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
     * @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodProvider;

    /**
     * @var PaymentTermViewProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentMethodViewRegistry = $this->createMock(PaymentMethodViewProvidersRegistry::class);

        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

        $this->provider = new PaymentTermViewProvider(
            $this->paymentMethodViewRegistry,
            $this->paymentMethodProvider
        );
    }

    public function testGetViewNotApplicable()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $paymentMethod
            ->expects(static::once())
            ->method('isApplicable')
            ->willReturn(false);

        $this->paymentMethodViewRegistry
            ->expects($this->never())
            ->method('getPaymentMethodViews');

        $this->assertNull($this->provider->getView($context));
    }

    public function testGetViewException()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->willThrowException(new \InvalidArgumentException());

        $this->assertNull($this->provider->getView($context));
    }

    public function testGetView()
    {
        /** @var PaymentContextInterface $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

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
