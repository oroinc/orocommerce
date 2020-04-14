<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Twig\WeightUnitValueExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WeightUnitValueExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var UnitValueFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var WeightUnitValueExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(UnitValueFormatterInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_shipping.formatter.weight_unit_value', $this->formatter)
            ->getContainer($this);

        $this->extension = new WeightUnitValueExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(WeightUnitValueExtension::NAME, $this->extension->getName());
    }

    public function testFormat()
    {
        $value = 42;
        $unit = new WeightUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_value', [$value, $unit])
        );
    }

    public function testFormatShort()
    {
        $value = 42;
        $unit = new WeightUnit();
        $unit->setCode('kg');
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_value_short', [$value, $unit])
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
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_code', [$value, $unitCode])
        );
    }
}
