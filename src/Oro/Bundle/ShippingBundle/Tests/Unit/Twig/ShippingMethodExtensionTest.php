<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierCheckerInterface;
use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\TwigFunction;

class ShippingMethodExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /**
     * @var ShippingMethodLabelFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var ShippingMethodExtension
     */
    protected $extension;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * @var ShippingMethodEnabledByIdentifierCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checker;

    protected function setUp(): void
    {
        $this->shippingMethodLabelFormatter = $this->createMock(ShippingMethodLabelFormatter::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->checker = $this->createMock(ShippingMethodEnabledByIdentifierCheckerInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_shipping.formatter.shipping_method_label', $this->shippingMethodLabelFormatter)
            ->add('event_dispatcher', $this->dispatcher)
            ->add('oro_shipping.checker.shipping_method_enabled', $this->checker)
            ->getContainer($this);

        $this->extension = new ShippingMethodExtension($container);
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new TwigFunction(
                    'get_shipping_method_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodLabel']
                ),
                new TwigFunction(
                    'get_shipping_method_type_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodTypeLabel']
                ),
                new TwigFunction(
                    'oro_shipping_method_with_type_label',
                    [$this->shippingMethodLabelFormatter, 'formatShippingMethodWithTypeLabel']
                ),
                new TwigFunction(
                    'oro_shipping_method_config_template',
                    [$this->extension, 'getShippingMethodConfigRenderData']
                ),
                new TwigFunction(
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
            ->with(static::isInstanceOf(ShippingMethodConfigDataEvent::class), ShippingMethodConfigDataEvent::NAME)
            ->will(static::returnCallback(function (ShippingMethodConfigDataEvent $event, $name) use ($methodName) {
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
            ->with(static::isInstanceOf(ShippingMethodConfigDataEvent::class), ShippingMethodConfigDataEvent::NAME)
            ->will(static::returnCallback(
                function (ShippingMethodConfigDataEvent $event, $name) use ($methodName, $template) {
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
