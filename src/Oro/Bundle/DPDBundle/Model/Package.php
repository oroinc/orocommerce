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
     * @var string
     */
    protected $contents;


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

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param string $contents
     * @return Package
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
        return $this;
    }


}