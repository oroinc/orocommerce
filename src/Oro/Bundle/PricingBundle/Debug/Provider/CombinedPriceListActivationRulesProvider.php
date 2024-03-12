<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Get information about CPL activation rules and Full Chain CPL (contains all PLs).
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class CombinedPriceListActivationRulesProvider
{
    public function __construct(
        private ManagerRegistry $registry,
        private ConfigManager $configManager,
        private CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
    }

    public function getActivationRules(CombinedPriceList $priceList): iterable
    {
        $activationRuleRepo = $this->registry->getRepository(CombinedPriceListActivationRule::class);

        return $activationRuleRepo->findBy(['fullChainPriceList' => $priceList], ['expireAt' => 'ASC']);
    }

    public function hasActivationRules(?CombinedPriceList $priceList): bool
    {
        if (!$priceList) {
            return false;
        }

        $activationRuleRepo = $this->registry->getRepository(CombinedPriceListActivationRule::class);

        return $activationRuleRepo->hasActivationRules($priceList);
    }

    public function getFullChainCpl(?Customer $customer = null, ?Website $website = null): ?CombinedPriceList
    {
        if ($website) {
            if ($customer) {
                $relation = $this->registry->getRepository(CombinedPriceListToCustomer::class)
                    ->getRelation($website, $customer);
                if ($relation) {
                    return $relation->getFullChainPriceList();
                }

                $customerGroup = $customer->getGroup();
            } else {
                $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup();
            }

            if ($customerGroup) {
                $relation = $this->registry->getRepository(CombinedPriceListToCustomerGroup::class)
                    ->getRelation($website, $customerGroup);
                if ($relation) {
                    return $relation->getFullChainPriceList();
                }
            }

            $relation = $this->registry->getRepository(CombinedPriceListToWebsite::class)
                ->getRelation($website);
            if ($relation) {
                return $relation->getFullChainPriceList();
            }
        }

        $configCplId = $this->configManager->get('oro_pricing.full_combined_price_list');

        return $this->registry->getRepository(CombinedPriceList::class)->find($configCplId);
    }
}
