<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Twig\OrderShippingExtension;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OrderShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    public function testGetShippingMethodLabel()
    {
        $testString = 'test';

        $labelTranslator = $this ->getMockBuilder(ShippingMethodLabelTranslator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $labelTranslator->expects(static::once())
            ->method('getShippingMethodWithTypeLabel')
            ->willReturn($testString);

        $container = self::getContainerBuilder()
            ->add(
                'oro_shipping.translator.shipping_method_label',
                $labelTranslator
            )
            ->getContainer($this);
        $extension = new OrderShippingExtension($container);

        static::assertSame(
            $testString,
            self::callTwigFunction($extension, 'oro_order_shipping_method_label', ['', ''])
        );
    }

    public function testGetShippingMethodLabelWithoutFormatter()
    {
        $methodName = 'method';
        $typeName = 'type';

        $container = self::getContainerBuilder()
            ->add(
                'oro_shipping.translator.shipping_method_label',
                null
            )
            ->getContainer($this);
        $extension = new OrderShippingExtension($container);

        static::assertSame(
            $methodName . ', ' . $typeName,
            self::callTwigFunction($extension, 'oro_order_shipping_method_label', [$methodName, $typeName])
        );
    }
}
