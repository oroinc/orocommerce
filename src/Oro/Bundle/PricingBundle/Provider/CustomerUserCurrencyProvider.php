<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides the currency for a given CustomerUser and Website.
 *
 * It first checks if the CustomerUser has a currency set in their website settings,
 * then falls back to the website's default currency, and finally to the system's default currency
 * if neither of the previous options are available or valid.
 */
class CustomerUserCurrencyProvider
{
    public function __construct(
        private readonly WebsiteManager $websiteManager,
        private readonly WebsiteCurrencyProvider $websiteCurrencyProvider,
        private readonly CurrencyProviderInterface $currencyProvider
    ) {
    }

    public function getCustomerUserCurrency(CustomerUser $customerUser, ?Website $website = null): string
    {
        $website ??= $customerUser->getWebsite() ??
            $this->websiteManager->getCurrentWebsite() ??
            $this->websiteManager->getDefaultWebsite();

        $existingCurrencies = $this->currencyProvider->getCurrencyList();
        $userSettings = $customerUser->getWebsiteSettings($website);
        $currency = null;

        // Try to get currency from user settings, if it exists and is valid.
        if ($userSettings) {
            $currency = (string)$userSettings->getCurrency();
            $currency = in_array($currency, $existingCurrencies, true) ? $currency : null;
        }

        // If currency is not set in user settings or is invalid, try to get it from website settings.
        if (!$currency) {
            $currency = $this->websiteCurrencyProvider->getWebsiteDefaultCurrency($website->getId());
        }

        // If currency is still not set, fallback to the default currency.
        if (!$currency) {
            $currency = $this->currencyProvider->getDefaultCurrency();
        }

        return $currency;
    }
}
