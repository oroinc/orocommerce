<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;

class Dimensions
{
    /**
     * @var DimensionsValue
     */
    protected $value;

    /**
     * @var LengthUnit
     */
    protected $unit;

    /**
     * @param float $length
     * @param float $width
     * @param float $height
     * @param LengthUnit $unit
     *
     * @return Dimensions
     */
    public static function create($length, $width, $height, LengthUnit $unit = null)
    {
        $value = DimensionsValue::create($length, $width, $height);

        /* @var $dimensions Dimensions */
        $dimensions = new static();
        $dimensions->setValue($value)->setUnit($unit);

        return $dimensions;
    }

    /**
     * @return DimensionsValue
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param DimensionsValue $value
     *
     * @return $this
     */
    public function setValue(DimensionsValue $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return LengthUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param LengthUnit $unit
     *
     * @return $this
     */
    public function setUnit(LengthUnit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }
}
