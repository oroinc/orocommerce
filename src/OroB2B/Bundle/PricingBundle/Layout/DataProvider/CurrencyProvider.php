<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class CurrencyProvider
{
    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param UserCurrencyManager $userCurrencyManager
     */
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
