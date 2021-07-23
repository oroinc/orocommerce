<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

class RemoveFromConfigurationPriceListEntityListener
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var PriceListConfigConverter
     */
    protected $configConverter;

    public function __construct(ConfigManager $configManager, PriceListConfigConverter $configConverter)
    {
        $this->configManager = $configManager;
        $this->configConverter = $configConverter;
    }

    public function preRemove(PriceList $priceList)
    {
        $configKey = Configuration::getConfigKeyByName(Configuration::DEFAULT_PRICE_LISTS);
        $configLists = $this->configConverter->convertFromSaved(
            $this->configManager->get($configKey)
        );

        $newConfigLists = array_filter(
            $configLists,
            function (PriceListConfig $priceListConfig) use ($priceList) {
                return $priceListConfig->getPriceList()->getId() !== $priceList->getId();
            }
        );

        if (count($newConfigLists) < count($configLists)) {
            $this->configManager->set($configKey, array_values($newConfigLists));
            $this->configManager->flush();
        }
    }
}
