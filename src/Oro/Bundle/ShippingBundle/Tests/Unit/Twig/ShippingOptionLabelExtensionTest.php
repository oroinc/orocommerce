<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\ShippingBundle\Twig\ShippingOptionLabelExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ShippingOptionLabelExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $lengthUnitLabelFormatter;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $weightUnitLabelFormatter;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $freightClassLabelFormatter;

    /** @var ShippingOptionLabelExtension */
    protected $extension;

    protected function setUp()
    {
        $this->lengthUnitLabelFormatter = $this->getMockBuilder(UnitLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->weightUnitLabelFormatter = $this->getMockBuilder(UnitLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->freightClassLabelFormatter = $this->getMockBuilder(UnitLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_shipping.formatter.length_unit_label', $this->lengthUnitLabelFormatter)
            ->add('oro_shipping.formatter.weight_unit_label', $this->weightUnitLabelFormatter)
            ->add('oro_shipping.formatter.freight_class_label', $this->freightClassLabelFormatter)
            ->getContainer($this);

        $this->extension = new ShippingOptionLabelExtension($container);
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->lengthUnitLabelFormatter,
            $this->weightUnitLabelFormatter,
            $this->freightClassLabelFormatter
        );
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingOptionLabelExtension::NAME, $this->extension->getName());
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     */
    public function testFormatLengthUnitLabel($code, $isShort, $isPlural, $expected)
    {
        $this->lengthUnitLabelFormatter->expects(self::once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_length_unit_format_label', [$code, $isShort, $isPlural])
        );
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     */
    public function testFormatWeightUnitLabel($code, $isShort, $isPlural, $expected)
    {
        $this->weightUnitLabelFormatter->expects(self::once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_weight_unit_format_label', [$code, $isShort, $isPlural])
        );
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     */
    public function testFormatFreightClassLabel($code, $isShort, $isPlural, $expected)
    {
        $this->freightClassLabelFormatter->expects(self::once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_freight_class_format_label', [$code, $isShort, $isPlural])
        );
    }

    /**
     * @return array
     */
    public function formatProvider()
    {
        return [
            'format full single' => [
                'code' => 'test_format',
                'isShort' => false,
                'isPlural' => false,
                'expected' => 'formated_full_single',
            ],
            'format short plural' => [
                'code' => 'test_format',
                'isShort' => true,
                'isPlural'=> true,
                'expected' => 'formated_short_plural',
            ],
        ];
    }
}
