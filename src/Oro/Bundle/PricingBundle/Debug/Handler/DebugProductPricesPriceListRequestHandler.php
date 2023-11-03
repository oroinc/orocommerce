<?php

namespace Oro\Bundle\PricingBundle\Debug\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to obtain price list by given website, customer and currencies,
 * decide whether tier prices should be shown or not
 */
class DebugProductPricesPriceListRequestHandler implements PriceListRequestHandlerInterface
{
    public const CUSTOMER_KEY = 'customer';
    public const WEBSITE_KEY = 'website';
    public const DATE_KEY = 'date';
    public const DETAILED_ASSIGNMENTS_KEY = 'showDetailedAssignments';
    public const SHOW_FULL_USED_CHAIN_KEY = 'showFullUsedChain';

    private RequestStack $requestStack;
    private ManagerRegistry $doctrine;
    private CombinedPriceListTreeHandler $combinedPriceListTreeHandler;

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        CombinedPriceListTreeHandler $combinedPriceListTreeHandler
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
        $this->combinedPriceListTreeHandler = $combinedPriceListTreeHandler;
    }

    public function getPriceList()
    {
        $website = $this->getWebsite();
        $customer = $this->getCustomer();

        return $this->combinedPriceListTreeHandler->getPriceList($customer, $website);
    }

    public function getWebsite(): Website
    {
        $request = $this->getRequest();

        $website = null;
        $websiteRepo = $this->doctrine->getRepository(Website::class);
        if ($request) {
            $websiteId = $request->get(self::WEBSITE_KEY);
            if ($websiteId) {
                $website = $websiteRepo->find($websiteId);
            }
        }

        if ($website) {
            return $website;
        }

        return $websiteRepo->getDefaultWebsite();
    }

    public function getCustomer(): ?Customer
    {
        $request = $this->getRequest();

        if (!$request) {
            return null;
        }

        $customerId = $request->get(self::CUSTOMER_KEY);
        if ($customerId) {
            return $this->doctrine->getRepository(Customer::class)->find($customerId);
        }

        return null;
    }

    public function getPriceListSelectedCurrencies(BasePriceList $priceList)
    {
        $priceListCurrencies = $priceList->getCurrencies();

        $request = $this->getRequest();

        if (!$request) {
            return $priceListCurrencies;
        }

        $currencies = $request->get(self::PRICE_LIST_CURRENCY_KEY, $priceListCurrencies);

        if (filter_var($currencies, FILTER_VALIDATE_BOOLEAN)) {
            return $priceListCurrencies;
        }

        if (null === $currencies) {
            return [];
        }

        $currencies = array_intersect($priceListCurrencies, (array)$currencies);

        sort($currencies);

        return $currencies;
    }

    public function getShowTierPrices()
    {
        $request = $this->getRequest();
        if (!$request) {
            return true;
        }

        return filter_var($request->get(self::TIER_PRICES_KEY, true), FILTER_VALIDATE_BOOLEAN);
    }

    public function getSelectedDate()
    {
        return $this->getRequest()?->get(self::DATE_KEY);
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getMainRequest();
    }

    public function getShowDetailedAssignmentInfo(): bool
    {
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::DETAILED_ASSIGNMENTS_KEY, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function getShowFullUsedChain(): bool
    {
        return true;

        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::SHOW_FULL_USED_CHAIN_KEY, false), FILTER_VALIDATE_BOOLEAN);
    }
}
