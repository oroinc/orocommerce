<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

class UnitValueFormatterTest extends UnitValueFormatterTestCase
{
    private const TRANSLATION_PREFIX = 'oro.product_unit';
    private const VALUE = 42.65;
    private const FORMATTED_VALUE = '42,65'; // emulate german localization with comma

    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $numberFormatter;

    protected function setUp()
    {
        parent::setUp();

        $this->numberFormatter = $this->createMock(NumberFormatter::class);
    }

    public function testFormatCodeWithNumberFormatter(): void
    {
        $formatter = $this->createFormatterWithNumberFormatter();

        $this->numberFormatter
            ->expects(self::once())
            ->method('formatDecimal')
            ->with(self::VALUE)
            ->willReturn(self::FORMATTED_VALUE); // emulate german localization

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.short_fraction_gt_1',
                ['%count%' => self::FORMATTED_VALUE]
            );

        $formatter->formatCode(self::VALUE, 'item', true);
    }

    public function testFormatShortWithNumberFormatter(): void
    {
        $formatter = $this->createFormatterWithNumberFormatter();

        $this->numberFormatter
            ->expects(self::once())
            ->method('formatDecimal')
            ->with(self::VALUE)
            ->willReturn(self::FORMATTED_VALUE); // emulate german localization

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.short_fraction_gt_1',
                ['%count%' => self::FORMATTED_VALUE]
            );

        $formatter->formatShort(self::VALUE, $this->createObject('item'));
    }

    public function testFormatWithNumberFormatter(): void
    {
        $formatter = $this->createFormatterWithNumberFormatter();

        $this->numberFormatter
            ->expects(self::once())
            ->method('formatDecimal')
            ->with(self::VALUE)
            ->willReturn(self::FORMATTED_VALUE); // emulate german localization

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.full_fraction_gt_1',
                ['%count%' => self::FORMATTED_VALUE]
            );

        $formatter->format(self::VALUE, $this->createObject('item'));
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code): MeasureUnitInterface
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }

    /**
     * @return UnitValueFormatter
     */
    protected function createFormatterWithNumberFormatter(): UnitValueFormatter
    {
        /** @var UnitValueFormatter $formatter */
        $formatter = $this->createFormatter();
        $formatter->setNumberFormatter($this->numberFormatter);

        return $formatter;
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter(): UnitValueFormatterInterface
    {
        $formatter = new UnitValueFormatter($this->translator, $this->numberFormatter);
        $formatter->setTranslationPrefix($this->getTranslationPrefix());

        return $formatter;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix(): string
    {
        return self::TRANSLATION_PREFIX;
    }
}
