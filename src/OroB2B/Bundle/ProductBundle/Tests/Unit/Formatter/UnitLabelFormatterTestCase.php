<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

abstract class UnitLabelFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    const TRANSLATION_PREFIX = '';

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var UnitLabelFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }

    protected function tearDown()
    {
        unset($this->formatter, $this->translator);
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @param string $expected
     */
    public function testFormat($unitCode, $isShort, $isPlural, $expected)
    {
        $this->translator->expects($this->once())->method('trans')->with($expected);
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
                'expected'  => static::TRANSLATION_PREFIX . '.kg.label.full',
            ],
            'format full plural' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'isPlural'  => true,
                'expected'  => static::TRANSLATION_PREFIX . '.kg.label.full_plural',
            ],
            'format short single' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'isPlural'   => false,
                'expected'  => static::TRANSLATION_PREFIX . '.item.label.short',
            ],
            'format short plural' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => static::TRANSLATION_PREFIX . '.item.label.short_plural',
            ],
            'empty code' => [
                'unitCode'  => '',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => 'N/A',
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
        $units = [$this->createObject('kg'), $this->createObject('item')];

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->will($this->returnValueMap([
                [static::TRANSLATION_PREFIX . '.kg.label.full', [], null, null, '_KG'],
                [static::TRANSLATION_PREFIX . '.kg.label.full_plural', [], null, null, '_KG_PLURAL'],
                [static::TRANSLATION_PREFIX . '.item.label.full', [], null, null, '_ITEM'],
                [static::TRANSLATION_PREFIX . '.item.label.full_plural', [], null, null, '_ITEM_PLURAL'],
                [static::TRANSLATION_PREFIX . '.kg.label.short', [], null, null, '_KG_SHORT'],
                [static::TRANSLATION_PREFIX . '.kg.label.short_plural', [], null, null, '_KG_SHORT_PLURAL'],
                [static::TRANSLATION_PREFIX . '.item.label.short', [], null, null, '_ITEM_SHORT'],
                [static::TRANSLATION_PREFIX . '.item.label.short_plural', [], null, null, '_ITEM_SHORT_PLURAL']
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

    /**
     * @param string $code
     * @return MeasureUnitInterface
     */
    abstract protected function createObject($code);
}
