<?php

namespace Oro\Bundle\CurrencyBundle\Model;

class OptionalPrice extends Price
{
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
