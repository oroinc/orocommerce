<?php

namespace OroB2B\Component\Duplicator\Test\Stub;

class RequestProductItem
{
    /**
     * @var ProductUnit
     */
    protected $unit;

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }
}
