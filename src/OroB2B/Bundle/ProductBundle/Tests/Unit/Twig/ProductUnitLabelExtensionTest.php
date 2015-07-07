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

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('orob2b_format_product_unit_label', $filters[0]->getName());
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFormat($unitCode, $isShort, $expected)
    {
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($unitCode, $isShort)
            ->willReturn($expected)
        ;

        $this->assertEquals($expected, $this->extension->format($unitCode, $isShort));
    }

    public function formatProvider()
    {
        return [
            'format' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'expected'  => 'kilogram',
            ],
            'format shosrt' => [
                'unitCode'  => 'kg',
                'isShort'   => true,
                'expected'  => 'kg',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitLabelExtension::NAME, $this->extension->getName());
    }
}
