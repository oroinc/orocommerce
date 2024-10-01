<?php

namespace Oro\Bundle\PricingBundle\Debug\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\CombinedPriceListActivationRulesProvider;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to obtain price list by given website, customer and currencies,
 * decide whether tier prices should be shown or not
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class DebugProductPricesPriceListRequestHandler implements PriceListRequestHandlerInterface
{
    public const CUSTOMER_KEY = 'customer';
    public const WEBSITE_KEY = 'website';
    public const DATE_KEY = 'date';
    public const DETAILED_ASSIGNMENTS_KEY = 'showDetailedAssignmentInfo';
    public const SHOW_DEVELOPERS_INFO = 'showDevelopersInfo';

    private RequestStack $requestStack;
    private ManagerRegistry $registry;
    private CombinedPriceListTreeHandler $combinedPriceListTreeHandler;
    private CombinedPriceListActivationRulesProvider $cplActivationRulesProvider;
    private CustomerUserRelationsProvider $customerUserRelationsProvider;

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        CombinedPriceListTreeHandler $combinedPriceListTreeHandler,
        CombinedPriceListActivationRulesProvider $cplActivationRulesProvider,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $doctrine;
        $this->combinedPriceListTreeHandler = $combinedPriceListTreeHandler;
        $this->cplActivationRulesProvider = $cplActivationRulesProvider;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
    }

    #[\Override]
    public function getPriceList(): ?CombinedPriceList
    {
        $rule = $this->getCplActivationRule();
        if ($rule) {
            return $rule->getCombinedPriceList();
        }

        return $this->getCurrentActivePriceList();
    }

    public function getCplActivationRule(): ?CombinedPriceListActivationRule
    {
        $date = $this->getSelectedDate();

        if ($date) {
            $fullCpl = $this->getFullChainCpl();

            if ($fullCpl) {
                $activationRuleRepo = $this->registry->getRepository(CombinedPriceListActivationRule::class);

                return $activationRuleRepo->getActualRuleByCpl($fullCpl, $date);
            }
        }

        return null;
    }

    public function getCurrentActivePriceList(): ?CombinedPriceList
    {
        $website = $this->getWebsite();
        $customer = $this->getCustomer();

        // Support anonymous
        if (!$customer) {
            $customer = $this->customerUserRelationsProvider->getCustomerIncludingEmpty();
        }

        return $this->combinedPriceListTreeHandler->getPriceList($customer, $website);
    }

    public function getFullChainCpl(): ?CombinedPriceList
    {
        return $this->cplActivationRulesProvider->getFullChainCpl($this->getCustomer(), $this->getWebsite());
    }

    public function getWebsite(): Website
    {
        $request = $this->getRequest();

        $website = null;
        $websiteRepo = $this->registry->getRepository(Website::class);
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
            return $this->registry->getRepository(Customer::class)->find($customerId);
        }

        return null;
    }

    #[\Override]
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

    #[\Override]
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
        $date = $this->getRequest()?->get(self::DATE_KEY);
        if (!$date) {
            return null;
        }

        try {
            return new \DateTime($date, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getShowDetailedAssignmentInfo(): bool
    {
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::DETAILED_ASSIGNMENTS_KEY, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function getShowDevelopersInfo(): bool
    {
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::SHOW_DEVELOPERS_INFO, false), FILTER_VALIDATE_BOOLEAN);
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getMainRequest();
    }
}
