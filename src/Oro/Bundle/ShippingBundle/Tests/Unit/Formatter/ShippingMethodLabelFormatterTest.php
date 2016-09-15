<?php

namespace Oro\src\Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

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
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethod = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new ShippingMethodLabelFormatter(
            $this->shippingMethodRegistry
        );
    }

    /**
     * @param string $shippingMethod
     * @param string $methodLabel
     * @param boolean $isGrouped
     */
    public function shippingMethodLabelMock($shippingMethod, $methodLabel, $isGrouped)
    {
        $this->shippingMethodRegistry
            ->expects($this->any())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($this->shippingMethod);
        $this->shippingMethod
            ->expects($this->any())
            ->method('getLabel')
            ->willReturn($methodLabel);
        $this->shippingMethod
            ->expects($this->once())
            ->method('isGrouped')
            ->willReturn($isGrouped);
    }

    /**
     * @param string $shippingMethod
     * @param string $shippingType
     * @param string $shippingTypeLabel
     */
    public function shippingMethodTypeLabelMock($shippingMethod, $shippingType, $shippingTypeLabel)
    {
        $this->shippingMethodRegistry
            ->expects($this->any())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($this->shippingMethod);
        $method = $this->getMockBuilder(ShippingMethodInterface::class)->getMock();
        $method->expects($this->any())
            ->method('getLabel')
            ->willReturn($shippingTypeLabel);
        $this->shippingMethod
            ->expects($this->once())
            ->method('getType')
            ->with($shippingType)
            ->willReturn($method);
    }

    /**
     * @dataProvider shippingMethodProvider
     * @param string $shippingMethod
     * @param string $shippingMethodLabel
     * @param string $expectedResult
     * @param boolean $isGrouped
     */
    public function testFormatShippingMethodLabel(
        $shippingMethod,
        $shippingMethodLabel,
        $expectedResult,
        $isGrouped
    ) {
        $this->shippingMethodLabelMock($shippingMethod, $shippingMethodLabel, $isGrouped);

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
                'expectedResult'           => '',
                'isGrouped'                => false
            ],
            [
                'shippingMethod'           => 'shipping_method_2',
                'shippingMethodLabel'      => 'Shipping Method 2 Label',
                'expectedResult'           => 'Shipping Method 2 Label',
                'isGrouped'                => true
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

    /**
     * @dataProvider shippingMethodWithTypeProvider
     * @param string $shippingMethod
     * @param string $shippingMethodLabel
     * @param string $shippingMethodType
     * @param string $shippingMethodTypeLabel
     * @param bool $isGrouped
     * @param string $expectedResult
     */
    public function testFormatShippingMethodWithType(
        $shippingMethod,
        $shippingMethodLabel,
        $shippingMethodType,
        $shippingMethodTypeLabel,
        $isGrouped,
        $expectedResult
    ) {
        $this->shippingMethodLabelMock($shippingMethod, $shippingMethodLabel, $isGrouped);
        $this->shippingMethodTypeLabelMock($shippingMethod, $shippingMethodType, $shippingMethodTypeLabel);

        $this->assertEquals(
            $expectedResult,
            $this->formatter->formatShippingMethodWithType($shippingMethod, $shippingMethodType)
        );
    }

    /**
     * @return array
     */
    public function shippingMethodWithTypeProvider()
    {
        return [
            [
                'shippingMethod'          => 'shipping_method_1',
                'shippingMethodLabel'     => 'Shipping Method 1',
                'shippingMethodType'      => 'shipping_type_1',
                'shippingTypeLabel'       => 'Type 1 Label',
                'isGrouped'                => false,
                'expectedResult'          => 'Type 1 Label'
            ],
            [
                'shippingMethod'          => 'shipping_method_2',
                'shippingMethodLabel'     => 'Shipping Method 2',
                'shippingMethodType'      => 'shipping_type_2',
                'shippingTypeLabel'       => 'Type 2 Label',
                'isGrouped'                => true,
                'expectedResult'          => 'Shipping Method 2, Type 2 Label'
            ],
        ];
    }
}
