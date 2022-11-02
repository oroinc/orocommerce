<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatterInterface;

class UnitValueFormatterTest extends UnitValueFormatterTestCase
{
    private const TRANSLATION_PREFIX = 'oro.product_unit';
    private const VALUE = 42.65;
    private const FORMATTED_VALUE = '42,65'; // emulate german localization with comma

    public function testFormatCodeWithGermanLocalization(): void
    {
        $formatter = $this->createFormatter();

        $this->configureFormatter(self::VALUE, self::FORMATTED_VALUE);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.short_fraction_gt_1',
                ['%count%' => self::VALUE, '%formattedCount%' => self::FORMATTED_VALUE]
            );

        $formatter->formatCode(self::VALUE, 'item', true);
    }

    public function testFormatShortWithNumberFormatterWithGermanLocalization(): void
    {
        $formatter = $this->createFormatter();

        $this->configureFormatter(self::VALUE, self::FORMATTED_VALUE);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.short_fraction_gt_1',
                ['%count%' => self::VALUE, '%formattedCount%' => self::FORMATTED_VALUE]
            );

        $formatter->formatShort(self::VALUE, $this->createObject('item'));
    }

    public function testFormatWithNumberFormatterWithGermanLocalization(): void
    {
        $formatter = $this->createFormatter();

        $this->configureFormatter(self::VALUE, self::FORMATTED_VALUE);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                $this->getTranslationPrefix() . '.item.value.full_fraction_gt_1',
                ['%count%' => self::VALUE, '%formattedCount%' => self::FORMATTED_VALUE]
            );

        $formatter->format(self::VALUE, $this->createObject('item'));
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject(string $code): MeasureUnitInterface
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
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
