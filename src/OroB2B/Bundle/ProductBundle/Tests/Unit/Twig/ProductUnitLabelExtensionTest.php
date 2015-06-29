<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Twig\ProductUnitLabelExtension;

class ProductUnitLabelExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitLabelExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductUnitLabelExtension($this->formatter);
    }

    public function testGetFilters()
    {
        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_product_unit_label', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('orob2b_format_short_product_unit_label', $filters[1]->getName());
    }

    public function testFormat()
    {
        $unitCode       = 'kg';
        $expectedResult = 'kilogram';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->extension->format($unitCode));
    }

    public function testFormatShort()
    {
        $unitCode       = 'kg';
        $expectedResult = 'kg';

        $this->formatter->expects($this->once())
            ->method('formatShort')
            ->with($unitCode)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->extension->formatShort($unitCode));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitLabelExtension::NAME, $this->extension->getName());
    }
}
