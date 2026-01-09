<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

/**
 * Defines the contract for formatting product quantities with their units of measure.
 *
 * Implementations of this interface provide methods to format numeric values with product units in both full
 * and short formats, supporting localized display of quantities throughout the application.
 */
interface UnitValueFormatterInterface
{
    /**
     * @param null|float|integer $value
     * @param MeasureUnitInterface|null $unit
     *
     * @return string
     */
    public function format($value, ?MeasureUnitInterface $unit = null);

    /**
     * @param null|float|integer $value
     * @param MeasureUnitInterface|null $unit
     *
     * @return string
     */
    public function formatShort($value, ?MeasureUnitInterface $unit = null);

    /**
     * @param float|integer $value
     * @param string $unitCode
     * @param boolean $isShort
     *
     * @return string
     */
    public function formatCode($value, $unitCode, $isShort = false);
}
