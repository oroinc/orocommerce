<?php

namespace OroB2B\src\OroB2B\Bundle\ShippingBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class ShippingMethodLabelFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodRegistry;

    /**
     * @var ShippingMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethod;

    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $formatter;

    public function setUp()
    {
        $this->shippingMethodRegistry = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethod = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new ShippingMethodLabelFormatter(
            $this->shippingMethodRegistry
        );
    }

    /**
     * @param string $shippingMethod
     * @param string $methodLabel
     */
    public function shippingMethodLabelMock($shippingMethod, $methodLabel)
    {
        $this->shippingMethodRegistry
            ->expects($this->once())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($this->shippingMethod);
        $this->shippingMethod
            ->expects($this->once())
            ->method('getLabel')
            ->willReturn($methodLabel);
    }

    /**
     * @param string $shippingMethod
     * @param string $shippingType
     * @param string $shippingTypeLabel
     */
    public function shippingMethodTypeLabelMock($shippingMethod, $shippingType, $shippingTypeLabel)
    {
        $this->shippingMethodRegistry
            ->expects($this->once())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($this->shippingMethod);
        $this->shippingMethod
            ->expects($this->once())
            ->method('getShippingTypeLabel')
            ->with($shippingType)
            ->willReturn($shippingTypeLabel);
    }

    /**
     * @dataProvider shippingMethodProvider
     * @param string $shippingMethod
     * @param string $shippingMethodLabel
     * @param string $expectedResult
     */
    public function testFormatShippingMethodLabel(
        $shippingMethod,
        $shippingMethodLabel,
        $expectedResult
    ) {

        $this->shippingMethodLabelMock($shippingMethod, $shippingMethodLabel);

        $this->assertEquals($expectedResult, $this->formatter->formatShippingMethodLabel($shippingMethod));
    }

    /**
     * @return array
     */
    public function shippingMethodProvider()
    {
        return [
            [
                'shippingMethod'           => 'shipping_method_1',
                'shippingMethodLabel'      => 'Shipping Method 1 Label',
                'expectedResult'          => 'Shipping Method 1 Label',
            ],
            [
                'shippingMethod'           => 'shipping_method_2',
                'shippingMethodLabel'      => 'Shipping Method 2 Label',
                'expectedResult'          => 'Shipping Method 2 Label',
            ],
        ];
    }

    /**
     * @dataProvider shippingMethodTypeProvider
     * @param string $shippingMethod
     * @param string $shippingMethodType
     * @param string $shippingMethodTypeLabel
     * @param string $expectedResult
     */
    public function testFormatShippingMethodTypeLabel(
        $shippingMethod,
        $shippingMethodType,
        $shippingMethodTypeLabel,
        $expectedResult
    ) {

        $this->shippingMethodTypeLabelMock($shippingMethod, $shippingMethodType, $shippingMethodTypeLabel);

        $this->assertEquals(
            $expectedResult,
            $this->formatter->formatShippingMethodTypeLabel($shippingMethod, $shippingMethodType)
        );
    }

    /**
     * @return array
     */
    public function shippingMethodTypeProvider()
    {
        return [
            [
                'shippingMethod'          => 'shipping_method_1',
                'shippingMethodType'      => 'shipping_type_1',
                'shippingTypeLabel'       => 'Shipping Method Type 1 Label',
                'expectedResult'          => 'Shipping Method Type 1 Label',
            ],
            [
                'shippingMethod'          => 'shipping_method_2',
                'shippingMethodType'      => 'shipping_type_2',
                'shippingTypeLabel'       => 'Shipping Method Type 2 Label',
                'expectedResult'          => 'Shipping Method Type 2 Label',
            ],
        ];
    }
}
