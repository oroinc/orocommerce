<?php

namespace OroB2B\Bundle\ProductBundle\Rounding;

use OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException;

interface RoundingServiceInterface
{
    const HALF_UP = 'half_up';
    const HALF_DOWN = 'half_down';
    const CEIL = 'ceil';
    const FLOOR = 'floor';

    /**
     * @param float|integer $value
     * @param integer $precision
     * @param string $roundType
     * @return float|int
     * @throws InvalidRoundingTypeException
     */
    public function round($value, $precision = null, $roundType = null);
}
