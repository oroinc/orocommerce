<?php

namespace Oro\Bundle\DPDBundle\Model;

class Package
{
    /**
     * Weight in kg
     * @var string
     */
    protected $weight;

    /**
     * @return string
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param string $weight
     * @return Package
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }
}