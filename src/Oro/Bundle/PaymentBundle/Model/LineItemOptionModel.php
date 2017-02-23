<?php

namespace Oro\Bundle\PaymentBundle\Model;

class LineItemOptionModel
{
    /** @var string */
    private $name = '';

    /** @var string */
    private $description = '';

    /** @var float */
    private $cost = 0.;

    /** @var float */
    private $qty = 0.;

    /** @var string */
    private $currency;

    /** @var string */
    private $unit;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     * @return $this
     */
    public function setCost($cost)
    {
        $this->cost = (float)$cost;

        return $this;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param float $qty
     * @return $this
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     * @return $this
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }
}
