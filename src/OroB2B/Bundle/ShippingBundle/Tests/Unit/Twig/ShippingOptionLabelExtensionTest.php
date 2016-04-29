<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

use OroB2B\Bundle\ShippingBundle\Twig\ShippingOptionLabelExtension;

class ShippingOptionLabelExtensionTest extends \PHPUnit_Framework_TestCase
{
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
        $this->lengthUnitLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->weightUnitLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->freightClassLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ShippingOptionLabelExtension(
            $this->lengthUnitLabelFormatter,
            $this->weightUnitLabelFormatter,
            $this->freightClassLabelFormatter
        );
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

    public function testGetFilters()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $this->assertCount(3, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_length_unit_format_label', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('orob2b_weight_unit_format_label', $filters[1]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[2]);
        $this->assertEquals('orob2b_freight_class_format_label', $filters[2]->getName());
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
        $this->assertExtensionCalled($this->lengthUnitLabelFormatter, 0, $code, $isShort, $isPlural, $expected);
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
        $this->assertExtensionCalled($this->weightUnitLabelFormatter, 1, $code, $isShort, $isPlural, $expected);
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
        $this->assertExtensionCalled($this->freightClassLabelFormatter, 2, $code, $isShort, $isPlural, $expected);
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

    /**
     * @param UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject $formatter
     * @param int $filterNumber
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     */
    protected function assertExtensionCalled(
        UnitLabelFormatter $formatter,
        $filterNumber,
        $code,
        $isShort,
        $isPlural,
        $expected
    ) {
        $formatter->expects($this->once())
            ->method('format')
            ->with($code, $isShort, $isPlural)
            ->willReturn($expected);

        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $this->assertEquals(
            $expected,
            call_user_func_array($filters[$filterNumber]->getCallable(), [$code, $isShort, $isPlural])
        );
    }
}
