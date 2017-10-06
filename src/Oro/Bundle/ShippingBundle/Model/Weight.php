<?php

namespace Oro\Bundle\ShippingBundle\Model;

use Oro\Bundle\ShippingBundle\Entity\WeightUnit;

class Weight
{
    /**
     * @var float
     */
    protected $value;

    /**
     * @var WeightUnit|null
     */
    protected $unit;

    /**
     * @param float $value
     * @param WeightUnit|null $unit
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
     * @return WeightUnit|null
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param WeightUnit|null $unit
     * @return $this
     */
    public function setUnit(WeightUnit $unit = null)
    {
        $this->unit = $unit;

        return $this;
    }
}
