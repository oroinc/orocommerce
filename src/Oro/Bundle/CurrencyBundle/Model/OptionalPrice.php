<?php

namespace Oro\Bundle\CurrencyBundle\Model;

class OptionalPrice extends Price
{
    /**
     * @param float $value
     * @param string $currency
     * @return OptionalPrice
     */
    public static function create($value = null, $currency = null)
    {
        return parent::create($value, $currency);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
