<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ShippingBundle\Formatter\DimensionsValueFormatter;
use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;

class DimensionsValueFormatterTest extends \PHPUnit_Framework_TestCase
{
    const TRANSLATION_PREFIX = 'orob2b.length_unit';

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var DimensionsValueFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter = new DimensionsValueFormatter($this->translator);
        $this->formatter->setTranslationPrefix(self::TRANSLATION_PREFIX);
    }

    protected function tearDown()
    {
        unset($this->formatter, $this->translator);
    }

    public function testFormatCodeShort()
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.short', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '42 x 42 x 42 translated',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), 'item', true)
        );
    }

    public function testFormatCodeFull()
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.full', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '42 x 42 x 42 translated',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), 'item')
        );
    }

    public function testFormatCodeNullValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(null, 'item')
        );
    }

    public function testFormatCodeEmptyValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(DimensionsValue::create(null, null, null), 'item')
        );
    }

    public function testFormatCodeEmptyCode()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), null)
        );
    }
}
