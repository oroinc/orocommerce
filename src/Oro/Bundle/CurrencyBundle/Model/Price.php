<?php

namespace Oro\Bundle\CurrencyBundle\Model;

class Price
{
    /** @var  int */
    protected $value;

    /** @var  string */
    protected $currency;

    /**
     * @param int $value
     * @param string $currency
     */
    public function __construct($value, $currency)
    {
        $this->setValue($value);
        $this->setCurrency($currency);
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Price value can not be empty');
        }

        $this->value = $value;
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
     */
    public function setCurrency($currency)
    {
        if (empty($currency)) {
            throw new \InvalidArgumentException('Price currency can not be empty');
        }

        $this->currency = $currency;
    }
}
