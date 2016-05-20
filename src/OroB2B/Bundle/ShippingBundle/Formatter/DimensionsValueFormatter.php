<?php

namespace OroB2B\Bundle\ShippingBundle\Formatter;

use OroB2B\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;

class DimensionsValueFormatter extends UnitValueFormatter
{
    /**
     * @param DimensionsValue $value
     * @param string $unitCode
     * @param boolean $isShort
     *
     * @return string
     */
    public function formatCode($value, $unitCode, $isShort = false)
    {
        $na = $this->translator->trans('N/A');

        if (!$value instanceof DimensionsValue || $value->isEmpty() || !$unitCode) {
            return $na;
        }

        $unitTranslationKey = sprintf(
            '%s.%s.label.%s',
            $this->getTranslationPrefix(),
            $unitCode,
            $isShort ? 'short' : 'full'
        );

        return sprintf(
            '%s %s',
            $this->formatValue($value, $na),
            $this->translator->trans($unitTranslationKey)
        );
    }

    /**
     * @param DimensionsValue $value
     * @param string $na
     * @return string
     */
    protected function formatValue(DimensionsValue $value, $na)
    {
        return sprintf(
            '%s x %s x %s',
            $value->getLength() ?: $na,
            $value->getWidth() ?: $na,
            $value->getHeight() ?: $na
        );
    }
}
