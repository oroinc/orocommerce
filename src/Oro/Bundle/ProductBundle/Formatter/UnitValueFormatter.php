<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Formats value representation adding unit description.
 */
class UnitValueFormatter extends AbstractUnitFormatter implements UnitValueFormatterInterface
{
    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    public function __construct(TranslatorInterface $translator, NumberFormatter $numberFormatter)
    {
        parent::__construct($translator);
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param null|float|integer $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function format($value, MeasureUnitInterface $unit = null)
    {
        return $this->formatCode($value, $unit ? $unit->getCode() : null);
    }

    /**
     * @param null|float|integer $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatShort($value, MeasureUnitInterface $unit = null)
    {
        return $this->formatCode($value, $unit ? $unit->getCode() : null, true);
    }

    /**
     * @param float|integer $value
     * @param string $unitCode
     * @param boolean $isShort
     *
     * @return string
     */
    public function formatCode($value, $unitCode, $isShort = false)
    {
        if (!is_numeric($value) || !$unitCode) {
            return $this->translator->trans('N/A');
        }
        $formattedCount = $this->formatScientificNotation($value);

        return $this->translator->trans(
            sprintf('%s.%s.value.%s', $this->getTranslationPrefix(), $unitCode, $this->getSuffix($value, $isShort)),
            [
                '%count%' => $value,
                '%formattedCount%' => $formattedCount
            ]
        );
    }

    /**
     * @param float $value
     * @param bool $isShort
     *
     * @return string
     */
    protected function getSuffix($value, $isShort)
    {
        $suffix = $isShort ? 'short' : 'full';
        if ((double)$value !== (double)(int)$value) {
            $suffix .= '_fraction';
            if ($value > 1) {
                $suffix .= '_gt_1';
            }
        }

        return $suffix;
    }

    /**
     * @param int|float|string $value
     *
     * @return string
     */
    protected function formatScientificNotation($value): string
    {
        // Need if $value has scientific notation format, as an example: 6.0E-10.
        if ($value != (int)$value) {
            $value = $this->numberFormatter->formatDecimal(
                $value,
                [\NumberFormatter::FRACTION_DIGITS => PHP_FLOAT_DIG]
            );
            return rtrim($value, '0');
        }

        return $this->numberFormatter->format($value, \NumberFormatter::TYPE_DEFAULT);
    }
}
