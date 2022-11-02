<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;

/**
 * Base class for subtotal calculations for different entities
 */
abstract class AbstractSubtotalProvider
{
    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var WebsiteCurrencyProvider
     */
    protected $websiteCurrencyProvider;

    public function __construct(SubtotalProviderConstructorArguments $arguments)
    {
        $this->currencyManager = $arguments->getCurrencyManager();
        $this->websiteCurrencyProvider = $arguments->getWebsiteCurrencyProvider();
    }

    /**
     * @param $entity
     * @return string
     */
    protected function getBaseCurrency($entity)
    {
        if ($entity instanceof CurrencyAwareInterface && $entity->getCurrency()) {
            return $entity->getCurrency();
        }
        if ($currency = $this->currencyManager->getUserCurrency()) {
            return $currency;
        }
        if ($entity instanceof WebsiteBasedCurrencyAwareInterface && $entity->getWebsite()) {
            return $this->websiteCurrencyProvider->getWebsiteDefaultCurrency($entity->getWebsite()->getId());
        }
        return $this->currencyManager->getDefaultCurrency();
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    protected function getExchangeRate($fromCurrency, $toCurrency)
    {
        /**
         * TODO: Need to define currency exchange logic. BB-124/BB-3274
         */
        return 1.0;
    }
}
