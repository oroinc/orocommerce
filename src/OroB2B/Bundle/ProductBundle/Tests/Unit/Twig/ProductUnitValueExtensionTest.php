<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Twig\ProductUnitValueExtension;

class ProductUnitValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitValueExtension
     */
    protected $extension;

    /**
     * @var ProductUnitValueExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ProductUnitValueExtension($this->formatter);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_product_unit_value', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('orob2b_format_short_product_unit_value', $filters[1]->getName());
    }

    public function testFormat()
    {
        $value = 42;
        $unit = (new ProductUnit())->setCode('kg');
        $expectedResult = '42 kilograms';

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->extension->format($value, $unit));
    }

    public function testFormatShort()
    {
        $value = 42;
        $unit = (new ProductUnit())->setCode('kg');
        $expectedResult = '42 kg';

        $this->formatter->expects($this->once())
            ->method('formatShort')
            ->with($value, $unit)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->extension->formatShort($value, $unit));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitValueExtension::NAME, $this->extension->getName());
    }
}
