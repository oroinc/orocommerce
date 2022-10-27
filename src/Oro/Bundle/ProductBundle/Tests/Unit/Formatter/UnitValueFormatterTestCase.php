<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class UnitValueFormatterTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $numberFormatter;

    /** @var UnitValueFormatterInterface */
    protected $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->formatter = $this->createFormatter();
    }

    public function testFormat()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($this->getTranslationPrefix() . '.kg.value.full', ['%count%' => 42, '%formattedCount%' => 42]);

        $this->configureFormatter(42, 42);

        $this->formatter->format(42, $this->createObject('kg'));
    }

    public function testFormatShort()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($this->getTranslationPrefix() . '.item.value.short', ['%count%' => 42,  '%formattedCount%' => 42]);

        $this->configureFormatter(42, 42);

        $this->formatter->formatShort(42, $this->createObject('item'));
    }

    public function testFormatCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($this->getTranslationPrefix() . '.item.value.short', ['%count%' => 42,  '%formattedCount%' => 42]);

        $this->configureFormatter(42, 42);

        $this->formatter->formatCode(42, 'item', true);
    }

    public function testFormatCodeFull()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($this->getTranslationPrefix() . '.item.value.full', ['%count%' => 42,  '%formattedCount%' => 42]);

        $this->configureFormatter(42, 42);

        $this->formatter->formatCode(42, 'item');
    }

    public function testFormatFractionCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.short_fraction',
                ['%count%' => 0.5, '%formattedCount%' => 0.5]
            );

        $this->configureFormatter(0.5, 0.5);

        $this->formatter->formatCode(0.5, 'item', true);
    }

    public function testFormatFractionGreaterThanOneCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.short_fraction_gt_1',
                ['%count%' => 1.5, '%formattedCount%' => 1.5]
            );

        $this->configureFormatter(1.5, 1.5);

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

    protected function configureFormatter(float|int $inputNumber, string|float $outputNumber): void
    {
        $this->numberFormatter->expects(self::once())
            ->method(is_int($inputNumber) ? 'format' : 'formatDecimal')
            ->with($inputNumber)
            ->willReturn($outputNumber);
    }

    abstract protected function createFormatter(): UnitValueFormatterInterface;

    abstract protected function getTranslationPrefix(): string;

    abstract protected function createObject(string $code): MeasureUnitInterface;
}
