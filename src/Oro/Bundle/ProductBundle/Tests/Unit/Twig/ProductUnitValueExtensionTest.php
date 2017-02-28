<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use Oro\Bundle\ProductBundle\Twig\ProductUnitValueExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductUnitValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ProductUnitValueFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var ProductUnitValueExtension */
    private $extension;

    public function setUp()
    {
        $this->formatter = $this->getMockBuilder(ProductUnitValueFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_product.formatter.product_unit_value', $this->formatter)
            ->getContainer($this);

        $this->extension = new ProductUnitValueExtension($container);
    }

    public function testFormat()
    {
        $value = 42;
        $unit = new ProductUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_product_unit_value', [$value, $unit])
        );
    }

    public function testFormatShort()
    {
        $value = 42;
        $unit = new ProductUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_short_product_unit_value', [$value, $unit])
        );
    }

    public function testFormatCode()
    {
        $value = 42;
        $unitCode = 'kg';
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatCode')
            ->with($value, $unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_product_unit_code', [$value, $unitCode])
        );
    }
}
