<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Symfony\Component\Translation\TranslatorInterface;

abstract class UnitValueFormatterTestCase extends \PHPUnit\Framework\TestCase
{
    const TRANSLATION_PREFIX = '';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var UnitValueFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
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

    public function testFormatFractionCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.short_fraction', 0.5);

        $this->formatter->formatCode(0.5, 'item', true);
    }

    public function testFormatFractionGreaterThanOneCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.short_fraction_gt_1', 1.5);

        $this->formatter->formatCode(1.5, 'item', true);
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
