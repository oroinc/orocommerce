<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Twig\DimensionsUnitValueExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DimensionsUnitValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $formatter;

    /** @var DimensionsUnitValueExtension */
    private $extension;

    public function setUp()
    {
        $this->formatter = $this->getMockBuilder(UnitValueFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_shipping.formatter.dimensions_unit_value', $this->formatter)
            ->getContainer($this);

        $this->extension = new DimensionsUnitValueExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(DimensionsUnitValueExtension::NAME, $this->extension->getName());
    }

    public function testFormat()
    {
        $value = 42;
        $unit = new LengthUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_dimensions_unit_format_value', [$value, $unit])
        );
    }

    public function testFormatShort()
    {
        $value = 42;
        $unit = new LengthUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_dimensions_unit_format_value_short', [$value, $unit])
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
            self::callTwigFilter($this->extension, 'oro_dimensions_unit_format_code', [$value, $unitCode])
        );
    }
}
