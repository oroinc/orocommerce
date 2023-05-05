<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShippingMethodLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var ShippingMethodLabelFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->formatter = new ShippingMethodLabelFormatter($this->shippingMethodProvider);
    }

    public function testFormatShippingMethodLabel(): void
    {
        $shippingMethodName = 'shipping_method';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);
        $shippingMethod->expects(self::never())
            ->method('getLabel');
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(false);

        $this->assertSame('', $this->formatter->formatShippingMethodLabel($shippingMethodName));
    }

    public function testFormatShippingMethodLabelForGroupedShippingMethod(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingMethodLabel = 'Shipping Method Label';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);
        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodLabel);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);

        $this->assertEquals($shippingMethodLabel, $this->formatter->formatShippingMethodLabel($shippingMethodName));
    }

    public function testFormatShippingMethodLabelWithShippingMethodNameIsNull(): void
    {
        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        $this->assertSame('', $this->formatter->formatShippingMethodLabel(null));
    }

    public function testFormatShippingMethodTypeLabel(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';
        $shippingMethodTypeLabel = 'Shipping Method Type Label';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);

        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodTypeLabel);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($type);

        $this->assertEquals(
            $shippingMethodTypeLabel,
            $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodDoesNotExist(): void
    {
        $shippingMethodName = 'shipping_method';

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn(null);

        $this->assertSame('', $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, 'shipping_type'));
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodTypeDoesNotExist(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($shippingMethodName)
            ->willReturn($shippingMethod);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn(null);

        $this->assertSame('', $this->formatter->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName));
    }

    public function testFormatShippingMethodTypeLabelWhenShippingMethodNameIsNull(): void
    {
        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        $this->assertSame('', $this->formatter->formatShippingMethodTypeLabel(null, 'shipping_type'));
    }

    public function testFormatShippingMethodTypeLabelWhenShippingTypeNameIsNull(): void
    {
        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        $this->assertSame('', $this->formatter->formatShippingMethodTypeLabel('shipping_method', null));
    }

    public function testFormatShippingMethodWithTypeLabel(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodName);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);

        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingTypeName);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($type);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodName . ', ' . $shippingTypeName,
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWithEmptyMethod(): void
    {
        $shippingMethodName = 'shipping_method';
        $shippingTypeName = 'shipping_type';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $shippingMethod->expects(self::never())
            ->method('getLabel');
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(false);

        $type = $this->createMock(ShippingMethodTypeInterface::class);
        $type->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingTypeName);
        $shippingMethod->expects(self::once())
            ->method('getType')
            ->with($shippingTypeName)
            ->willReturn($type);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingTypeName,
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
        );
    }

    public function testFormatShippingMethodWithTypeLabelWhenShippingMethodNameIsNull(): void
    {
        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        self::assertSame('', $this->formatter->formatShippingMethodWithTypeLabel(null, 'shipping_type'));
    }

    public function testFormatShippingMethodWithTypeLabelWhenShippingTypeNameIsNull(): void
    {
        $shippingMethodName = 'shipping_method';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);

        $shippingMethod->expects(self::once())
            ->method('getLabel')
            ->willReturn($shippingMethodName);
        $shippingMethod->expects(self::once())
            ->method('isGrouped')
            ->willReturn(true);
        $shippingMethod->expects(self::never())
            ->method('getType');

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        self::assertEquals(
            $shippingMethodName . ', ',
            $this->formatter->formatShippingMethodWithTypeLabel($shippingMethodName, null)
        );
    }
}
