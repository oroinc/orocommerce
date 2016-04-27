<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_shipping_weight_unit")
 * @ORM\Entity
 */
class WeightUnit
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    protected $code;

    /**
     * @var array
     *
     * @ORM\Column(name="conversion_rates", type="array", nullable=true)
     */
    protected $conversionRates = [];

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return WeightUnit
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param array $conversionRates
     *
     * @return WeightUnit
     */
    public function setConversionRates(array $conversionRates = [])
    {
        $this->conversionRates = $conversionRates;

        return $this;
    }

    /**
     * @return array
     */
    public function getConversionRates()
    {
        return $this->conversionRates;
    }
}
