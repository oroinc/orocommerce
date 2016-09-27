<?php

namespace Oro\Bundle\PaymentBundle\Model;

class LineItemOptionModel
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var float */
    private $cost;

    /** @var int */
    private $qty;

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
        $this->name = $this->truncateString($name, 36);

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
        $this->description = $this->truncateString($description, 35);

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
     * @return int
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty)
    {
        $this->qty = (int)$qty;

        return $this;
    }

    /**
     * @param string $string
     * @param int $length
     * @return string
     */
    private function truncateString($string, $length)
    {
        return substr($string, 0, $length);
    }
}
