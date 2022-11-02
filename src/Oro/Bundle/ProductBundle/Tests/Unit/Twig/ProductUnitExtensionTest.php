<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ProductBundle\Twig\ProductUnitExtension;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUnitExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnitLabelFormatterInterface */
    private $labelFormatter;

    /** @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $valueFormatter;

    /** @var UnitVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $unitVisibility;

    /** @var ProductUnitExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->labelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->valueFormatter = $this->createMock(UnitValueFormatterInterface::class);
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.formatter.product_unit_label', $this->labelFormatter)
            ->add('oro_product.formatter.product_unit_value', $this->valueFormatter)
            ->add('oro_product.visibility.unit', $this->unitVisibility)
            ->getContainer($this);

        $this->extension = new ProductUnitExtension($container);
    }

    /**
     * @dataProvider formatLabelProvider
     */
    public function testFormatLabel(string $unitCode, bool $isShort, bool $isPlural, string $expected)
    {
        $this->labelFormatter->expects($this->once())
            ->method('format')
            ->with($unitCode, $isShort, $isPlural)
            ->willReturn($expected);

        $this->assertEquals(
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
                'unitCode'  => 'kg',
                'isShort'   => false,
                'isPlural'  => false,
                'expected'  => 'kilogram',
            ],
            'format short plural' => [
                'unitCode'  => 'kg',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => 'kgs',
            ],
        ];
    }

    /**
     * @dataProvider formatLabelShortProvider
     */
    public function testFormatLabelShort(string $unitCode, bool $isPlural, string $expected)
    {
        $this->labelFormatter->expects($this->once())
            ->method('format')
            ->with($unitCode, true, $isPlural)
            ->willReturn($expected);

        $this->assertEquals(
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
                'unitCode'  => 'kg',
                'isPlural'  => false,
                'expected'  => 'kilogram',
            ],
            'format plural' => [
                'unitCode'  => 'kg',
                'isPlural'  => true,
                'expected'  => 'kgs',
            ],
        ];
    }

    public function testFormatValue()
    {
        $value = 42;
        $unit = new ProductUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->valueFormatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_product_unit_value', [$value, $unit])
        );
    }

    public function testFormatValueShort()
    {
        $value = 42;
        $unit = new ProductUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->valueFormatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_short_product_unit_value', [$value, $unit])
        );
    }

    public function testFormatValueCode()
    {
        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->valueFormatter->expects($this->once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_product_unit_code', [$value, $unitCode])
        );
    }

    public function testIsUnitCodeVisible()
    {
        $code = 'test';

        $this->unitVisibility->expects(self::once())
            ->method('isUnitCodeVisible')
            ->with($code)
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'oro_is_unit_code_visible', [$code]));
    }
}
