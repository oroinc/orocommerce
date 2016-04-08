<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;

class PaymentMethodsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodViewRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PaymentMethodsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new PaymentMethodsProvider($this->registry);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(PaymentMethodsProvider::NAME, $this->provider->getIdentifier());
    }

    public function testGetDataEmpty()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->registry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->willReturn([]);

        $data = $this->provider->getData($context);
        $this->assertEmpty($data);
    }

    public function testGetData()
    {
        /**
         * @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context
         */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $view = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $view->expects($this->any())->method('getLabel')->will($this->returnValue('label'));
        $view->expects($this->any())->method('getBlock')->will($this->returnValue('block'));
        $view->expects($this->any())->method('getOptions')->will($this->returnValue([]));

        $this->registry->expects($this->once())
            ->method('getPaymentMethodViews')
            ->willReturn(['payment' => $view]);

        $data = $this->provider->getData($context);
        $this->assertEquals(['payment' => ['label' => 'label', 'block' => 'block', 'options' => []]], $data);
    }
}
