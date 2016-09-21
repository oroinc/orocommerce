<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Twig\ProductUnitLabelExtension;

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
        $this->formatter = $this->getMockBuilder('Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
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
        $this->assertEquals('oro_format_product_unit_label', $filters[0]->getName());
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     *
     * @dataProvider formatProvider
     */
    public function testFormat($unitCode, $isShort, $isPlural, $expected)
    {
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($unitCode, $isShort, $isPlural)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->extension->format($unitCode, $isShort, $isPlural));
    }

    /**
     * @return array
     */
    public function formatProvider()
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

    public function testGetName()
    {
        $this->assertEquals(ProductUnitLabelExtension::NAME, $this->extension->getName());
    }
}
