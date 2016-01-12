<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;

class CombinedPriceListsBuilder
{
    /**
     * @var PriceListCollectionProvider
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var WebsiteCombinedPriceListsBuilder
     */
    protected $websiteCombinedPriceListBuilder;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $combinedPriceListGarbageCollector;

    public function build()
    {
        $this->updatePriceListsOnCurrentLevel();
        $this->updatePriceListsOnChildrenLevels();
        $this->combinedPriceListGarbageCollector->cleanCombinedPriceLists();
    }

    protected function updatePriceListsOnCurrentLevel()
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByConfig();
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

        $combinedPriceListId = $this->configManager->get($this->getConfigKeyToPriceList());
        if ($combinedPriceListId != $actualCombinedPriceList->getId()) {
            $this->connectNewPriceList($actualCombinedPriceList);
        }
    }

    protected function updatePriceListsOnChildrenLevels()
    {
        $this->websiteCombinedPriceListBuilder->buildForAll();
    }

    /**
     * @param CombinedPriceListProvider $combinedPriceListProvider
     */
    public function setCombinedPriceListProvider($combinedPriceListProvider)
    {
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    /**
     * @param PriceListCollectionProvider $priceListCollectionProvider
     */
    public function setPriceListCollectionProvider(PriceListCollectionProvider $priceListCollectionProvider)
    {
        $this->priceListCollectionProvider = $priceListCollectionProvider;
    }

    /**
     * @param WebsiteCombinedPriceListsBuilder $websiteCPLBuilder
     */
    public function setWebsiteCombinedPriceListBuilder(WebsiteCombinedPriceListsBuilder $websiteCPLBuilder)
    {
        $this->websiteCombinedPriceListBuilder = $websiteCPLBuilder;
    }

    /**
     * @param CombinedPriceListGarbageCollector $CPLGarbageCollector
     */
    public function setCombinedPriceListGarbageCollector(CombinedPriceListGarbageCollector $CPLGarbageCollector)
    {
        $this->combinedPriceListGarbageCollector = $CPLGarbageCollector;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param CombinedPriceList $priceList
     */
    protected function connectNewPriceList(CombinedPriceList $priceList)
    {
        $this->configManager->set($this->getConfigKeyToPriceList(), $priceList->getId());
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
