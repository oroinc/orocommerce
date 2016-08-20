<?php

namespace Oro\Component\Duplicator\Test\Stub;

class ProductUnit
{
    /**
     * @var string
     */
    protected $unit;

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }
}
