<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ShippingMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ShippingMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingMethodLabelFormatter;

    /** @var ShippingMethodExtension */
    protected $extension;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
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

        $container = self::getContainerBuilder()
            ->add('oro_shipping.formatter.shipping_method_label', $this->shippingMethodLabelFormatter)
            ->add('event_dispatcher', $this->dispatcher)
            ->getContainer($this);

        $this->extension = new ShippingMethodExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingMethodExtension::SHIPPING_METHOD_EXTENSION_NAME, $this->extension->getName());
    }

    public function testGetShippingMethodConfigRenderDataDefault()
    {
        $methodName = 'method_1';

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(ShippingMethodConfigDataEvent::NAME)
            ->will(static::returnCallback(function ($name, ShippingMethodConfigDataEvent $event) use ($methodName) {
                static::assertEquals($methodName, $event->getMethodIdentifier());
                $event->setTemplate(ShippingMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE);
            }));

        self::assertEquals(
            ShippingMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            self::callTwigFunction($this->extension, 'oro_shipping_method_config_template', [$methodName])
        );

        //test cache
        self::assertEquals(
            ShippingMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            self::callTwigFunction($this->extension, 'oro_shipping_method_config_template', [$methodName])
        );
    }

    public function testGetShippingMethodConfigRenderData()
    {
        $methodName = 'method_1';
        $template = 'Bundle:template.html.twig';

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(ShippingMethodConfigDataEvent::NAME)
            ->will(static::returnCallback(
                function ($name, ShippingMethodConfigDataEvent $event) use ($methodName, $template) {
                    static::assertEquals($methodName, $event->getMethodIdentifier());
                    $event->setTemplate($template);
                }
            ));

        self::assertEquals(
            $template,
            self::callTwigFunction($this->extension, 'oro_shipping_method_config_template', [$methodName])
        );
    }
}
