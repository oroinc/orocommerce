<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\PricingBundle\Provider\CustomerUserCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Provides the currency for a given RFP Request.
 *
 * When a CustomerUser is present, delegates fully to {@see CustomerUserCurrencyProvider}
 * (which handles the website→system fallback internally). Otherwise falls back to the website's
 * default currency, then to the system default.
 */
class RfqCurrencyProvider
{
    public function __construct(
        private readonly CustomerUserCurrencyProvider $customerUserCurrencyProvider,
        private readonly WebsiteCurrencyProvider $websiteCurrencyProvider,
        private readonly CurrencyProviderInterface $currencyProvider
    ) {
    }

    public function getRfqCurrency(Request $request): string
    {
        $customerUser = $request->getCustomerUser();
        $website = $request->getWebsite();

        if ($customerUser !== null) {
            return $this->customerUserCurrencyProvider->getCustomerUserCurrency($customerUser, $website);
        }

        if ($website !== null) {
            $currency = $this->websiteCurrencyProvider->getWebsiteDefaultCurrency($website->getId());
            if ($currency) {
                return $currency;
            }
        }

        return $this->currencyProvider->getDefaultCurrency();
    }
}
