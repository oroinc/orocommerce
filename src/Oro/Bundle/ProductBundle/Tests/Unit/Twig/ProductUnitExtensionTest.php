<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitPrecisionLabelFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Twig\ProductUnitExtension;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductUnitExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private UnitLabelFormatterInterface|MockObject $labelFormatter;

    private UnitValueFormatterInterface|MockObject $valueFormatter;

    private UnitVisibilityInterface|MockObject $unitVisibility;

    private UnitPrecisionLabelFormatter|MockObject $unitPrecisionLabelFormatter;

    private ProductUnitExtension $extension;

    protected function setUp(): void
    {
        $this->labelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->valueFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);
        $this->unitPrecisionLabelFormatter = $this->createMock(UnitPrecisionLabelFormatter::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.formatter.product_unit_label', $this->labelFormatter)
            ->add('oro_product.formatter.product_unit_value', $this->valueFormatter)
            ->add('oro_product.visibility.unit', $this->unitVisibility)
            ->add('oro_product.formatter.unit_precision_label', $this->unitPrecisionLabelFormatter)
            ->getContainer($this);

        $this->extension = new ProductUnitExtension($container);
    }

    /**
     * @dataProvider formatLabelProvider
     */
    public function testFormatLabel(string $unitCode, bool $isShort, bool $isPlural, string $expected): void
    {
        $this->labelFormatter->expects(self::once())
            ->method('format')
            ->with($unitCode, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_format_product_unit_label',
                [$unitCode, $isShort, $isPlural]
            )
        );
    }

    public function formatLabelProvider(): array
    {
        return [
            'format full single' => [
                'unitCode' => 'kg',
                'isShort' => false,
                'isPlural' => false,
                'expected' => 'kilogram',
            ],
            'format short plural' => [
                'unitCode' => 'kg',
                'isShort' => true,
                'isPlural' => true,
                'expected' => 'kgs',
            ],
        ];
    }

    /**
     * @dataProvider formatLabelShortProvider
     */
    public function testFormatLabelShort(string $unitCode, bool $isPlural, string $expected): void
    {
        $this->labelFormatter->expects(self::once())
            ->method('format')
            ->with($unitCode, true, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter(
                $this->extension,
                'oro_format_short_product_unit_label',
                [$unitCode, $isPlural]
            )
        );
    }

    public function formatLabelShortProvider(): array
    {
        return [
            'format single' => [
                'unitCode' => 'kg',
                'isPlural' => false,
                'expected' => 'kilogram',
            ],
            'format plural' => [
                'unitCode' => 'kg',
                'isPlural' => true,
                'expected' => 'kgs',
            ],
        ];
    }

    public function testFormatValue(): void
    {
        $value = 42;
        $unit = new ProductUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->valueFormatter->expects(self::once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_product_unit_value', [$value, $unit])
        );
    }

    public function testFormatValueShort(): void
    {
        $value = 42;
        $unit = new ProductUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->valueFormatter->expects(self::once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_short_product_unit_value', [$value, $unit])
        );
    }

    public function testFormatValueCode(): void
    {
        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->valueFormatter->expects(self::once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_product_unit_code', [$value, $unitCode])
        );
    }

    public function testIsUnitCodeVisible(): void
    {
        $code = 'test';

        $this->unitVisibility->expects(self::once())
            ->method('isUnitCodeVisible')
            ->with($code)
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'oro_is_unit_code_visible', [$code]));
    }

    public function testFormatUnitPrecisionLabel(): void
    {
        $unitCode = 'kg';
        $precision = 2;
        $expectedResult = 'item (fractional, 2 decimal digits)';

        $this->unitPrecisionLabelFormatter
            ->expects(self::once())
            ->method('formatUnitPrecisionLabel')
            ->with($unitCode, $precision)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_format_product_unit_precision_label', [$unitCode, $precision])
        );
    }
}
