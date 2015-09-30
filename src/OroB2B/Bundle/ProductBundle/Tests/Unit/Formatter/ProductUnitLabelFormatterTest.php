<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitLabelFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator   = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter    = new ProductUnitLabelFormatter($this->translator);
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param string $expected
     *
     * @dataProvider formatProvider
     */
    public function testFormat($unitCode, $isShort, $expected)
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($expected);

        $this->formatter->format($unitCode, $isShort);
    }

    /**
     * @param bool $isShort
     * @param array $expected
     *
     * @dataProvider formatChoicesProvider
     */
    public function testFormatChoices($isShort, array $expected)
    {
        $units = [
            (new ProductUnit())->setCode('kg'),
            (new ProductUnit())->setCode('item'),
        ];

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->will($this->returnValueMap([
                ['orob2b.product_unit.kg.label.full', [], null, null, '_KG'],
                ['orob2b.product_unit.item.label.full', [], null, null, '_ITEM'],
                ['orob2b.product_unit.kg.label.short', [], null, null, '_KG_SHORT'],
                ['orob2b.product_unit.item.label.short', [], null, null, '_ITEM_SHORT'],
            ]));

        $this->assertEquals($expected, $this->formatter->formatChoices($units, $isShort));
    }

    /**
     * @return array
     */
    public function formatProvider()
    {
        return [
            'format' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'expected'  => 'orob2b.product_unit.kg.label.full',
            ],
            'format short' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'expected'  => 'orob2b.product_unit.item.label.short',
            ],
        ];
    }

    /**
     * @return array
     */
    public function formatChoicesProvider()
    {
        return [
            'full labels' => [
                'isShort' => false,
                'expected' => [
                    'kg' => '_KG',
                    'item' => '_ITEM'
                ],
            ],
            'short labels' => [
                'isShort' => true,
                'expected' => [
                    'kg' => '_KG_SHORT',
                    'item' => '_ITEM_SHORT'
                ],
            ],
        ];
    }
}
