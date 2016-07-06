<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class Subtotal
{
    const OPERATION_ADD = 1;
    const OPERATION_SUBTRACTION = 2;
    const OPERATION_IGNORE = 3;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var string
     */
    protected $currency;

    /**
     * Type operation for calculate total
     *
     * @var integer
     */
    protected $operation = self::OPERATION_ADD;

    /**
     * Visibility in total
     *
     * @var boolean
     */
    protected $visible;

    /**
     * Extra data
     *
     * @var array
     */
    protected $data;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Subtotal
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return Subtotal
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return Subtotal
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

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
     *
     * @return Subtotal
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get operation type
     *
     * @return integer
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set operation type
     *
     * @param integer $operation
     *
     * @return Subtotal
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Check visibility in total block
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * Set operation type
     *
     * @param boolean $visible
     *
     * @return Subtotal
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }


    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return Subtotal
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->getType(),
            'label' => $this->getLabel(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'visible' => $this->isVisible(),
            'data' => $this->getData(),
        ];
    }

    /**
     * @return Price
     */
    public function getTotalPrice()
    {
        return (new Price())->setCurrency($this->getCurrency())->setValue($this->getAmount());
    }
}
