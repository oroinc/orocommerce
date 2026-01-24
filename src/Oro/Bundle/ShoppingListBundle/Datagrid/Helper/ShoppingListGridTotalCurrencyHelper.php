<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Helper;

use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;

/**
 * Generates SQL `CASE` statements for determining the appropriate currency in shopping list datagrids.
 *
 * This helper is used in shopping list grids to dynamically select the correct currency based on the website
 * associated with each shopping list. It builds SQL CASE expressions that map website IDs
 * to their configured currencies, falling back to the default system currency when no specific website currency
 * is configured. This ensures that shopping list totals are displayed in the correct currency for multi-website
 * and multi-currency OroCommerce installations.
 */
class ShoppingListGridTotalCurrencyHelper
{
    /**
     * @var WebsiteCurrencyProvider
     */
    protected $websiteCurrencyProvider;

    /**
     * @var DefaultCurrencyProviderInterface
     */
    protected $defaultCurrencyProvider;

    public function __construct(
        DefaultCurrencyProviderInterface $defaultCurrencyProvider,
        WebsiteCurrencyProvider $websiteCurrencyProvider
    ) {
        $this->websiteCurrencyProvider = $websiteCurrencyProvider;
        $this->defaultCurrencyProvider = $defaultCurrencyProvider;
    }

    /**
     * @return string
     */
    public function getCurrencyStatement()
    {
        $defaultCurrency = $this->defaultCurrencyProvider->getDefaultCurrency();

        $groupedIds = $this->geWebsitesIdsGroupedByCurrencies();
        if (!$groupedIds) {
            return "'$defaultCurrency'";
        }
        if (count($groupedIds) === 1) {
            reset($groupedIds);
            $currency = key($groupedIds);
            if ($currency === $defaultCurrency) {
                return "'$currency'";
            }
        }

        $statement = 'CASE ';
        foreach ($groupedIds as $currency => $websiteIds) {
            if ($currency !== $defaultCurrency) {
                $statement .= " WHEN shopping_list.website IN (" . implode(',', $websiteIds)  . ") THEN '$currency' ";
            }
        }
        return $statement . " ELSE '$defaultCurrency' END";
    }

    /**
     * @return array
     */
    protected function geWebsitesIdsGroupedByCurrencies()
    {
        $groupedIds = [];
        foreach ($this->websiteCurrencyProvider->getAllWebsitesCurrencies() as $websiteId => $currency) {
            $groupedIds[$currency][] = $websiteId;
        }
        return $groupedIds;
    }
}
