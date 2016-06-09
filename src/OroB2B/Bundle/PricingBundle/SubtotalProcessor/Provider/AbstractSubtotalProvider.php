<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

abstract class AbstractSubtotalProvider
{
    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(UserCurrencyManager $currencyManager)
    {
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param $entity
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if (!$entity instanceof CurrencyAwareInterface || !$entity->getCurrency()) {
            return $this->currencyManager->getUserCurrency();
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
