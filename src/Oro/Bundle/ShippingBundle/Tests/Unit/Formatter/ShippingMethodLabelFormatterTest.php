<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class ShippingMethodLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingMethodProvider;

    /**
     * @var ShippingMethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingMethod;

    /**
     * @var ShippingMethodLabelFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->shippingMethod = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new ShippingMethodLabelFormatter(
            $this->shippingMethodProvider
        );
    }

    /**
     * @param string $shippingMethod
     * @param string $methodLabel
     * @param boolean $isGrouped
     */
    public function shippingMethodLabelMock($shippingMethod, $methodLabel, $isGrouped)
    {
        $this->shippingMethodProvider
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
        $this->shippingMethodProvider
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

    public function testFormatShippingMethodWithTypeLabel()
    {
        $methodName = 'method';
        $typeName = 'type';

        $methodType = $this->createConfiguredMock(
            ShippingMethodTypeInterface::class,
            ['getLabel' => $typeName]
        );

        $this->shippingMethod->expects(static::any())
            ->method('getLabel')
            ->willReturn($methodName);
        $this->shippingMethod->expects(static::any())
            ->method('isGrouped')
            ->willReturn(true);
        $this->shippingMethod->expects(static::any())
            ->method('getType')
            ->willReturn($methodType);

        $this->shippingMethodProvider->expects(static::any())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $label = $this->formatter->formatShippingMethodWithTypeLabel($methodName, $typeName);

        static::assertSame($methodName . ', ' . $typeName, $label);
    }

    public function testFormatShippingMethodWithTypeLabelWithEmptyMethod()
    {
        $methodName = 'method';
        $typeName = 'type';

        $methodType = $this->createConfiguredMock(
            ShippingMethodTypeInterface::class,
            ['getLabel' => $typeName]
        );

        $this->shippingMethod->expects(static::any())
            ->method('getLabel')
            ->willReturn($methodName);
        $this->shippingMethod->expects(static::any())
            ->method('isGrouped')
            ->willReturn(false);
        $this->shippingMethod->expects(static::any())
            ->method('getType')
            ->willReturn($methodType);

        $this->shippingMethodProvider->expects(static::any())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $label = $this->formatter->formatShippingMethodWithTypeLabel($methodName, $typeName);

        static::assertSame($typeName, $label);
    }
}
