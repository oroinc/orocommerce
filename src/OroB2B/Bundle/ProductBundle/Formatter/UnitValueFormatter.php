<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;

class UnitValueFormatter extends AbstractUnitFormatter
{
    /**
     * @param float|integer $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function format($value, MeasureUnitInterface $unit)
    {
        return $this->formatCode($value, $unit->getCode());
    }

    /**
     * @param float|integer $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatShort($value, MeasureUnitInterface $unit)
    {
        return $this->formatCode($value, $unit->getCode(), true);
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
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException(
                sprintf('The parameter "value" must be a numeric, but it is of type %s.', gettype($value))
            );
        }

        return $this->translator->transChoice(
            sprintf('%s.%s.value.%s', $this->getTranslationPrefix(), $unitCode, $isShort ? 'short' : 'full'),
            $value,
            [
                '%count%' => $value
            ]
        );
    }
}
