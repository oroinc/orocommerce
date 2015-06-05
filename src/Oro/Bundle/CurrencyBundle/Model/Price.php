<?php

namespace Oro\Bundle\CurrencyBundle\Model;

class Price
{
    /** @var  int */
    protected $value;

    /** @var  string */
    protected $currency;

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setValue($value)
    {
        if (empty($value)) {
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
