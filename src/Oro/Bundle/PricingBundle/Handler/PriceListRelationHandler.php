<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

/**
 * Checks whether the price list is used in at least one relation or configuration.
 */
class PriceListRelationHandler implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private const DEFAULT_PRICE_LIST_KEY = Configuration::ROOT_NODE . '.' . Configuration::DEFAULT_PRICE_LIST;

    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    private WebsiteProviderInterface $websiteProvider;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $registry,
        WebsiteProviderInterface $websiteProvider
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $registry;
        $this->websiteProvider = $websiteProvider;
    }

    public function isPriceListAlreadyUsed(PriceList $priceList): bool
    {
        if (!$this->isFeaturesEnabled()) {
            throw new \LogicException('Only flat pricing engine is supported.');
        }

        if ($this->isPriceListUsedInRelations($priceList) || $this->isPriceListUsedInConfig($priceList)) {
            return true;
        }

        return false;
    }

    private function isPriceListUsedInRelations(PriceList $priceList): bool
    {
        $classes = [PriceListToCustomer::class, PriceListToCustomerGroup::class];
        foreach ($classes as $class) {
            /** @var PriceListToCustomerRepository|PriceListToCustomerGroupRepository $priceListRepository */
            $priceListRepository = $this->doctrine->getRepository($class);
            if ($priceListRepository->hasRelationWithPriceList($priceList)) {
                return true;
            }
        }

        return false;
    }

    private function isPriceListUsedInConfig(PriceList $priceList): bool
    {
        $configs = $this->configManager->getValues(
            self::DEFAULT_PRICE_LIST_KEY,
            $this->websiteProvider->getWebsites(),
            false,
            true
        );

        $filteredConfigs = array_filter($configs, fn ($config) => $config['value'] == $priceList->getId());

        return (bool) $filteredConfigs;
    }
}
