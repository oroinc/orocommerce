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
    private $numberFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param NumberFormatter $numberFormatter
     */
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

        return $this->translator->trans(
            sprintf('%s.%s.value.%s', $this->getTranslationPrefix(), $unitCode, $this->getSuffix($value, $isShort)),
            [
                '%count%' => $value,
                '%formattedCount%' => $this->numberFormatter->formatDecimal($value)
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
}
