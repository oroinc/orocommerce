<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * Provides currency information for layout rendering.
 *
 * Exposes user currency preferences and available currencies to layout templates,
 * enabling currency-aware pricing display on the storefront.
 */
class CurrencyProvider
{
    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    public function __construct(UserCurrencyManager $userCurrencyManager)
    {
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @return null|string
     */
    public function getDefaultCurrency()
    {
        return $this->userCurrencyManager->getDefaultCurrency();
    }

    /**
     * @return array
     */
    public function getAvailableCurrencies()
    {
        return $this->userCurrencyManager->getAvailableCurrencies();
    }

    /**
     * @return null|string
     */
    public function getUserCurrency()
    {
        return $this->userCurrencyManager->getUserCurrency();
    }
}
