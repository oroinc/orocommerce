<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitValueFormatter;

abstract class UnitValueFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    const TRANSLATION_PREFIX = '';

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var UnitValueFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }

    protected function tearDown()
    {
        unset($this->formatter, $this->translator);
    }

    public function testFormat()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.kg.value.full', 42);

        $this->formatter->format(42, $this->createObject('kg'));
    }

    public function testFormatShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.short', 42);

        $this->formatter->formatShort(42, $this->createObject('item'));
    }

    public function testFormatCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.short', 42);

        $this->formatter->formatCode(42, 'item', true);
    }

    public function testFormatCodeFull()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.full', 42);

        $this->formatter->formatCode(42, 'item');
    }

    public function testFormatWithInvalidValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A')
            ->willReturn('N/A');

        $this->assertEquals('N/A', $this->formatter->formatShort('test', $this->createObject('item')));
    }

    /**
     * @param string $code
     * @return MeasureUnitInterface
     */
    abstract protected function createObject($code);
}
