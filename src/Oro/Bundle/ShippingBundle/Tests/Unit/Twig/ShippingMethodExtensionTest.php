<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
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

    /**
     * @var ShippingMethodEnabledByIdentifierCheckerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checker;

    public function setUp()
    {
        $this->shippingMethodLabelFormatter = $this->createMock(ShippingMethodLabelFormatter::class);

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->checker = $this->createMock(ShippingMethodEnabledByIdentifierCheckerInterface::class);

        $this->extension = new ShippingMethodExtension(
            $this->shippingMethodLabelFormatter,
            $this->dispatcher,
            $this->checker
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
                    'oro_shipping_method_with_type_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodWithTypeLabel']
                ),
                new \Twig_SimpleFunction(
                    'oro_shipping_method_config_template',
                    [$this->extension, 'getShippingMethodConfigRenderData']
                ),
                new \Twig_SimpleFunction(
                    'oro_shipping_method_enabled',
                    [$this->extension, 'isShippingMethodEnabled']
                )
            ],
            $this->extension->getFunctions()
        );
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
            $this->extension->getShippingMethodConfigRenderData($methodName)
        );

        //test cache
        self::assertEquals(
            ShippingMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            $this->extension->getShippingMethodConfigRenderData($methodName)
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

        self::assertEquals($template, $this->extension->getShippingMethodConfigRenderData($methodName));
    }

    public function testIsShippingMethodEnabled()
    {
        $methodIdentifier = 'method_1';

        $this->checker
            ->expects(static::once())
            ->method('isEnabled')
            ->with($methodIdentifier)
            ->willReturn(true);

        self::assertTrue($this->extension->isShippingMethodEnabled($methodIdentifier));
    }
}
