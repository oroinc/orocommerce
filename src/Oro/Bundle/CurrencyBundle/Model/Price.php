<?php

namespace Oro\Bundle\CurrencyBundle\Model;

class Price
{
    /** @var float */
    protected $value;

    /** @var string */
    protected $currency;

    /**
     * @param float $value
     * @param string $currency
     * @return Price
     */
    public static function create($value, $currency)
    {
        /* @var $price self */
        $price = new static();
        $price->setValue($value)
            ->setCurrency($currency);

        return $price;
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
        if (null === $value) {
            throw new \InvalidArgumentException('Price value can not be empty');
        }

        $this->value = $value;

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
        if (empty($currency)) {
            throw new \InvalidArgumentException('Price currency can not be empty');
        }

        $this->currency = $currency;

        return $this;
    }
}
