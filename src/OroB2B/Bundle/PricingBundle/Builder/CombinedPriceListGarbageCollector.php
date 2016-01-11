<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;

class CombinedPriceListGarbageCollector
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $combinedPriceListClass;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListsRepository;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function cleanCombinedPriceLists()
    {
        $configCombinedPriceList = $this->configManager->get($this->getConfigKeyToPriceList());
        $exceptPriceLists = [];
        if ($configCombinedPriceList) {
            $exceptPriceLists[] = $configCombinedPriceList;
        }
        $this->getCombinedPriceListsRepository()->deleteUnusedPriceLists($exceptPriceLists);
    }

    /**
     * @param string $combinedPriceListClass
     */
    public function setCombinedPriceListClass($combinedPriceListClass)
    {
        $this->combinedPriceListClass = $combinedPriceListClass;
        $this->combinedPriceListsRepository = null;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListsRepository()
    {
        if (!$this->combinedPriceListsRepository) {
            $this->combinedPriceListsRepository = $this->registry->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListsRepository;
    }

    /**
     * @return string
     */
    protected function getConfigKeyToPriceList()
    {
        $key = implode(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            [OroB2BPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
        );

        return $key;
    }
}
