<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;

abstract class AbstractSubtotalProvider
{
    /**
     * @param $entity
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if (!$entity instanceof CurrencyAwareInterface) {
            return 'USD';
        } else {
            return $entity->getCurrency();
        }
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        /**
         * TODO: Need to define currency exchange logic. BB-124
         */
        return 1.0;
    }
}
