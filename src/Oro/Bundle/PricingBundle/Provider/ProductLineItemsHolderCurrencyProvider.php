<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;

/**
 * Detects currency for the given instance of line items aware class.
 * Decision map:
 * 1. Currency of the line items holder itself if it implements {@see CurrencyAwareInterface}.
 * 2. Currency of the currently logged-in user.
 * 3. Default currency of the website related to the line items holder if it
 * implements {@see WebsiteBasedCurrencyAwareInterface}.
 * 4. Default currency taken from the system configuration.
 */
class ProductLineItemsHolderCurrencyProvider
{
    private UserCurrencyManager $userCurrencyManager;

    private WebsiteCurrencyProvider $websiteCurrencyProvider;

    public function __construct(
        UserCurrencyManager $userCurrencyManager,
        WebsiteCurrencyProvider $websiteCurrencyProvider
    ) {
        $this->userCurrencyManager = $userCurrencyManager;
        $this->websiteCurrencyProvider = $websiteCurrencyProvider;
    }

    public function getCurrencyForLineItemsHolder(
        ProductLineItemsHolderInterface|LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
    ): string {
        if ($lineItemsHolder instanceof CurrencyAwareInterface && $lineItemsHolder->getCurrency()) {
            return $lineItemsHolder->getCurrency();
        }

        if ($currency = $this->userCurrencyManager->getUserCurrency()) {
            return $currency;
        }

        if ($lineItemsHolder instanceof WebsiteBasedCurrencyAwareInterface && $lineItemsHolder->getWebsite()) {
            return $this->websiteCurrencyProvider->getWebsiteDefaultCurrency($lineItemsHolder->getWebsite()->getId());
        }

        return $this->userCurrencyManager->getDefaultCurrency();
    }
}
