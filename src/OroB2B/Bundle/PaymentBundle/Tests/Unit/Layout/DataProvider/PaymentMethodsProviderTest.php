<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PaymentMethodViewRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PaymentContextProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentContextProvider;

    /**
     * @var PaymentMethodsProvider
     */
    protected $provider;

    /** @var array */
    protected $contextData = ['contextData' => 'data'];

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PaymentMethodsProvider($this->registry, $this->paymentContextProvider);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(PaymentMethodsProvider::NAME, $this->provider->getIdentifier());
    }

    public function testGetDataEmpty()
    {
        $context = new LayoutContext();

        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn($this->contextData);

        $this->registry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->with($this->contextData)
            ->willReturn([]);

        $data = $this->provider->getData($context);
        $this->assertEmpty($data);
    }

    public function testGetData()
    {
        $context = new LayoutContext();

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getLabel')->willReturn('label');
        $view->expects($this->once())->method('getBlock')->willReturn('block');
        $view->expects($this->once())
            ->method('getOptions')
            ->with($this->contextData)
            ->willReturn([]);

        $this->registry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->willReturn(['payment' => $view]);

        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->willReturn($this->contextData);

        $data = $this->provider->getData($context);
        $this->assertEquals(['payment' => ['label' => 'label', 'block' => 'block', 'options' => []]], $data);
    }

    public function testGetDataEntityFromContext()
    {
        $entity = new \stdClass();

        $context = new LayoutContext();
        $context->data()->set('entity', 'entity', $entity);

        $contextData = ['entity' => $entity];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with($context, $this->identicalTo($entity))
            ->willReturn($contextData);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with($contextData);

        $this->registry->expects($this->once())->method('getPaymentMethodViews')->willReturn(['payment' => $view]);

        $this->provider->getData($context);
    }

    public function testGetDataCheckoutFromContext()
    {
        $checkout = new \stdClass();

        $context = new LayoutContext();
        $context->data()->set('checkout', 'checkout', $checkout);

        $contextData = ['entity' => $checkout];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with($context, $this->identicalTo($checkout))
            ->willReturn($contextData);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with($contextData);

        $this->registry->expects($this->once())->method('getPaymentMethodViews')->willReturn(['payment' => $view]);

        $this->provider->getData($context);
    }

    public function testGetDataEntityPriorToCheckoutFromContext()
    {
        $entity = new \stdClass();
        $checkout = new \stdClass();

        $context = new LayoutContext();
        $context->data()->set('entity', 'entity', $entity);
        $context->data()->set('checkout', 'checkout', $checkout);

        $contextData = ['entity' => $entity];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with($context, $this->identicalTo($entity))
            ->willReturn($contextData);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with($contextData);

        $this->registry->expects($this->once())->method('getPaymentMethodViews')->willReturn(['payment' => $view]);

        $this->provider->getData($context);
    }
}
