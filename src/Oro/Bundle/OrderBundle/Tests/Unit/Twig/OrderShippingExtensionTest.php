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

        $labelTranslator = $this ->createMock(ShippingMethodLabelTranslator::class);
        $labelTranslator->expects(self::once())
            ->method('getShippingMethodWithTypeLabel')
            ->willReturn($testString);

        $container = self::getContainerBuilder()
            ->add(
                'oro_shipping.translator.shipping_method_label',
                $labelTranslator
            )
            ->getContainer($this);
        $extension = new OrderShippingExtension($container);

        self::assertSame(
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

        self::assertSame(
            $methodName . ', ' . $typeName,
            self::callTwigFunction($extension, 'oro_order_shipping_method_label', [$methodName, $typeName])
        );
    }
}
