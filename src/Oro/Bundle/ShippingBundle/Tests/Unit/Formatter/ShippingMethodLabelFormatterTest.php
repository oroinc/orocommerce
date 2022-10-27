<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class ShippingMethodLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var ShippingMethodInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethod;

    /** @var ShippingMethodLabelFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->formatter = new ShippingMethodLabelFormatter($this->shippingMethodProvider);
    }

    public function shippingMethodLabelMock(string $shippingMethod, string $methodLabel, bool $isGrouped): void
    {
        $this->shippingMethodProvider->expects($this->any())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($this->shippingMethod);
        $this->shippingMethod->expects($this->any())
            ->method('getLabel')
            ->willReturn($methodLabel);
        $this->shippingMethod->expects($this->once())
            ->method('isGrouped')
            ->willReturn($isGrouped);
    }

    public function shippingMethodTypeLabelMock(
        string $shippingMethod,
        string $shippingType,
        string $shippingTypeLabel
    ): void {
        $this->shippingMethodProvider->expects($this->any())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($this->shippingMethod);
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects($this->any())
            ->method('getLabel')
            ->willReturn($shippingTypeLabel);
        $this->shippingMethod->expects($this->once())
            ->method('getType')
            ->with($shippingType)
            ->willReturn($method);
    }

    /**
     * @dataProvider shippingMethodProvider
     */
    public function testFormatShippingMethodLabel(
        string $shippingMethod,
        string $shippingMethodLabel,
        string $expectedResult,
        bool $isGrouped
    ) {
        $this->shippingMethodLabelMock($shippingMethod, $shippingMethodLabel, $isGrouped);

        $this->assertEquals($expectedResult, $this->formatter->formatShippingMethodLabel($shippingMethod));
    }

    public function shippingMethodProvider(): array
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
     */
    public function testFormatShippingMethodTypeLabel(
        string $shippingMethod,
        string $shippingMethodType,
        string $shippingMethodTypeLabel,
        string $expectedResult
    ) {
        $this->shippingMethodTypeLabelMock($shippingMethod, $shippingMethodType, $shippingMethodTypeLabel);

        $this->assertEquals(
            $expectedResult,
            $this->formatter->formatShippingMethodTypeLabel($shippingMethod, $shippingMethodType)
        );
    }

    public function shippingMethodTypeProvider(): array
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

        $this->shippingMethod->expects(self::any())
            ->method('getLabel')
            ->willReturn($methodName);
        $this->shippingMethod->expects(self::any())
            ->method('isGrouped')
            ->willReturn(true);
        $this->shippingMethod->expects(self::any())
            ->method('getType')
            ->willReturn($methodType);

        $this->shippingMethodProvider->expects(self::any())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $label = $this->formatter->formatShippingMethodWithTypeLabel($methodName, $typeName);

        self::assertSame($methodName . ', ' . $typeName, $label);
    }

    public function testFormatShippingMethodWithTypeLabelWithEmptyMethod()
    {
        $methodName = 'method';
        $typeName = 'type';

        $methodType = $this->createConfiguredMock(
            ShippingMethodTypeInterface::class,
            ['getLabel' => $typeName]
        );

        $this->shippingMethod->expects(self::any())
            ->method('getLabel')
            ->willReturn($methodName);
        $this->shippingMethod->expects(self::any())
            ->method('isGrouped')
            ->willReturn(false);
        $this->shippingMethod->expects(self::any())
            ->method('getType')
            ->willReturn($methodType);

        $this->shippingMethodProvider->expects(self::any())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $label = $this->formatter->formatShippingMethodWithTypeLabel($methodName, $typeName);

        self::assertSame($typeName, $label);
    }
}
