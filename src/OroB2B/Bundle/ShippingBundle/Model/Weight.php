<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

class Weight
{
    /**
     * @var float
     */
    protected $value;

    /**
     * @var WeightUnit
     */
    protected $unit;

    /**
     * @param float $value
     * @param WeightUnit $unit
     * @return Weight
     */
    public static function create($value, WeightUnit $unit = null)
    {
        /* @var $weight self */
        $weight = new static();
        $weight->setValue($value)->setUnit($unit);

        return $weight;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return WeightUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param WeightUnit $unit
     * @return $this
     */
    public function setUnit(WeightUnit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }
}
