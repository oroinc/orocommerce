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
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter = new ProductUnitLabelFormatter($this->translator);
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
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($expected);

        $this->formatter->format($unitCode, $isShort, $isPlural);
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
                'expected'  => 'orob2b.product_unit.kg.label.full',
            ],
            'format full plural' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'isPlural'  => true,
                'expected'  => 'orob2b.product_unit.kg.label.full_plural',
            ],
            'format short single' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'isPlural'   => false,
                'expected'  => 'orob2b.product_unit.item.label.short',
            ],
            'format short plural' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => 'orob2b.product_unit.item.label.short_plural',
            ],
        ];
    }

    /**
     * @param bool $isShort
     * @param bool $isPlural
     * @param array $expected
     *
     * @dataProvider formatChoicesProvider
     */
    public function testFormatChoices($isShort, $isPlural, array $expected)
    {
        $units = [
            (new ProductUnit())->setCode('kg'),
            (new ProductUnit())->setCode('item'),
        ];

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->will($this->returnValueMap([
                ['orob2b.product_unit.kg.label.full', [], null, null, '_KG'],
                ['orob2b.product_unit.kg.label.full_plural', [], null, null, '_KG_PLURAL'],
                ['orob2b.product_unit.item.label.full', [], null, null, '_ITEM'],
                ['orob2b.product_unit.item.label.full_plural', [], null, null, '_ITEM_PLURAL'],
                ['orob2b.product_unit.kg.label.short', [], null, null, '_KG_SHORT'],
                ['orob2b.product_unit.kg.label.short_plural', [], null, null, '_KG_SHORT_PLURAL'],
                ['orob2b.product_unit.item.label.short', [], null, null, '_ITEM_SHORT'],
                ['orob2b.product_unit.item.label.short_plural', [], null, null, '_ITEM_SHORT_PLURAL'],
            ]));

        $this->assertEquals($expected, $this->formatter->formatChoices($units, $isShort, $isPlural));
    }

    /**
     * @return array
     */
    public function formatChoicesProvider()
    {
        return [
            'format choices full single' => [
                'isShort' => false,
                'isPlural' => false,
                'expected' => [
                    'kg' => '_KG',
                    'item' => '_ITEM'
                ],
            ],
            'format choices full plural' => [
                'isShort' => false,
                'isPlural' => true,
                'expected' => [
                    'kg' => '_KG_PLURAL',
                    'item' => '_ITEM_PLURAL'
                ],
            ],
            'format choices short single' => [
                'isShort' => true,
                'isPlural' => false,
                'expected' => [
                    'kg' => '_KG_SHORT',
                    'item' => '_ITEM_SHORT'
                ],
            ],
            'format choices short plural' => [
                'isShort' => true,
                'isPlural' => true,
                'expected' => [
                    'kg' => '_KG_SHORT_PLURAL',
                    'item' => '_ITEM_SHORT_PLURAL'
                ],
            ],
        ];
    }
}
