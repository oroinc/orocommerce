<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;

class SubtotalProviderConstructorArguments
{
    /** @var UserCurrencyManager */
    protected $currencyManager;

    /** @var WebsiteCurrencyProvider */
    protected $websiteCurrencyProvider;

    public function __construct(UserCurrencyManager $currencyManager, WebsiteCurrencyProvider $websiteCurrencyProvider)
    {
        $this->currencyManager = $currencyManager;
        $this->websiteCurrencyProvider = $websiteCurrencyProvider;
    }

    /**
     * @return UserCurrencyManager
     */
    public function getCurrencyManager()
    {
        return $this->currencyManager;
    }

    /**
     * @return WebsiteCurrencyProvider
     */
    public function getWebsiteCurrencyProvider()
    {
        return $this->websiteCurrencyProvider;
    }
}
