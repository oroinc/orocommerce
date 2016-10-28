<?php

namespace Oro\src\Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShippingMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var ShippingMethodExtension
     */
    protected $extension;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    public function setUp()
    {
        $this->shippingMethodLabelFormatter = $this
            ->getMockBuilder(ShippingMethodLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new ShippingMethodExtension(
            $this->shippingMethodLabelFormatter,
            $this->dispatcher
        );
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction(
                    'get_shipping_method_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodLabel']
                ),
                new \Twig_SimpleFunction(
                    'get_shipping_method_type_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodTypeLabel']
                ),
                new \Twig_SimpleFunction(
                    'oro_shipping_method_config_render_data',
                    [$this->extension, 'getShippingMethodConfigRenderData']
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingMethodExtension::SHIPPING_METHOD_EXTENSION_NAME, $this->extension->getName());
    }

    public function testGetShippingMethodConfigRenderData()
    {
        $methodName = 'method_1';
        $event = new ShippingMethodConfigDataEvent($methodName);

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(ShippingMethodConfigDataEvent::NAME, $event);

        $this->extension->getShippingMethodConfigRenderData($methodName);

        //test cache
        $this->extension->getShippingMethodConfigRenderData($methodName);
    }

    public function testGetShippingMethodConfigRenderDataDefault()
    {
        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->willReturn('');

        self::assertEquals(
            'OroShippingBundle:ShippingRule:config.html.twig',
            $this->extension->getShippingMethodConfigRenderData('method_1')
        );
    }
}
