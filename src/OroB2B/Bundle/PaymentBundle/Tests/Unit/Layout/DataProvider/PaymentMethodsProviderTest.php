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

        $this->registry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->willReturn([]);

        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn([]);

        $data = $this->provider->getData($context);
        $this->assertEmpty($data);
    }

    public function testGetData()
    {
        $context = new LayoutContext();

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->any())->method('getLabel')->will($this->returnValue('label'));
        $view->expects($this->any())->method('getBlock')->will($this->returnValue('block'));
        $view->expects($this->any())->method('getOptions')->will($this->returnValue([]));

        $this->registry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->willReturn(['payment' => $view]);

        $this->paymentContextProvider->expects($this->any())->method('processContext')->willReturn([]);

        $data = $this->provider->getData($context);
        $this->assertEquals(['payment' => ['label' => 'label', 'block' => 'block', 'options' => []]], $data);
    }

    public function testGetDataEntityFromContext()
    {
        $entity = new \stdClass();

        $context = new LayoutContext();
        $context->data()->set('entity', 'entity', $entity);

        $this->paymentContextProvider->expects($this->once())->method('processContext')
            ->willReturn(['entity' => $entity]);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with(
            $this->callback(
                function ($options) use ($entity) {
                    $this->assertInternalType('array', $options);
                    $this->assertArrayHasKey('entity', $options);
                    $this->assertSame($entity, $options['entity']);

                    return true;
                }
            )
        );

        $this->registry->expects($this->once())->method('getPaymentMethodViews')->willReturn(['payment' => $view]);

        $this->provider->getData($context);
    }

    public function testGetDataCheckoutFromContext()
    {
        $checkout = new \stdClass();

        $context = new LayoutContext();
        $context->data()->set('checkout', 'checkout', $checkout);

        $this->paymentContextProvider->expects($this->once())->method('processContext')
            ->willReturn(['entity' => $checkout]);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with(
            $this->callback(
                function ($options) use ($checkout) {
                    $this->assertInternalType('array', $options);
                    $this->assertArrayHasKey('entity', $options);
                    $this->assertSame($checkout, $options['entity']);

                    return true;
                }
            )
        );

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

        $this->paymentContextProvider->expects($this->once())->method('processContext')
            ->willReturn(['entity' => $entity]);

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->once())->method('getOptions')->with(
            $this->callback(
                function ($options) use ($entity) {
                    $this->assertInternalType('array', $options);
                    $this->assertArrayHasKey('entity', $options);
                    $this->assertSame($entity, $options['entity']);

                    return true;
                }
            )
        );

        $this->registry->expects($this->once())->method('getPaymentMethodViews')->willReturn(['payment' => $view]);

        $this->provider->getData($context);
    }
}
