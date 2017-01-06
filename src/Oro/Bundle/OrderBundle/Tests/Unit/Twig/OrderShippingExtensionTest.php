<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\Twig\OrderShippingExtension;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;

class OrderShippingExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShippingMethodLabelTranslator  */
    private $labelTranslator;

    public function setUp()
    {
        $this->labelTranslator = $this
            ->getMockBuilder(ShippingMethodLabelTranslator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetFunctionsReturnsCorrectDefinitions()
    {
        $extension = new OrderShippingExtension();

        $functions = [
            new \Twig_SimpleFunction(
                'oro_order_shipping_method_label',
                [$extension, 'getShippingMethodLabel']
            ),
        ];

        static::assertEquals($functions, $extension->getFunctions());
    }

    public function testGetShippingMethodLabel()
    {
        $testString = 'test';

        $this->labelTranslator->expects(static::once())
            ->method('getShippingMethodWithTypeLabel')
            ->willReturn($testString);

        $extension = new OrderShippingExtension();
        $extension->setShippingLabelFormatter($this->labelTranslator);

        $label = $extension->getShippingMethodLabel('', '');

        static::assertSame($testString, $label);
    }

    public function testGetShippingMethodLabelWithoutFormatter()
    {
        $methodName = 'method';
        $typeName = 'type';
        $extension = new OrderShippingExtension();

        $label = $extension->getShippingMethodLabel($methodName, $typeName);

        static::assertSame($methodName . ', ' . $typeName, $label);
    }
}
