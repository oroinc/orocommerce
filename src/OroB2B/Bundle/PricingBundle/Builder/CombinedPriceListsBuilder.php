<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

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
     * @var PriceListConfigConverter
     */
    protected $configConverter;

    /**
     * @param ConfigManager $configManager
     * @param PriceListConfigConverter $configConverter
     */
    public function __construct(ConfigManager $configManager, PriceListConfigConverter $configConverter)
    {
        $this->configManager = $configManager;
        $this->configConverter = $configConverter;
    }

    public function build()
    {
        $this->updatePriceListsOnCurrentLevel();
        $this->updatePriceListsOnChildrenLevels();
        $this->deleteUnusedPriceLists();
    }

    protected function updatePriceListsOnCurrentLevel()
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByConfig();
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

    }

    protected function updatePriceListsOnChildrenLevels()
    {

    }

    protected function deleteUnusedPriceLists()
    {

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
    public function setPriceListCollectionProvider($priceListCollectionProvider)
    {
        $this->priceListCollectionProvider = $priceListCollectionProvider;
    }

    /**
     * @param WebsiteCombinedPriceListsBuilder $websiteCombinedPriceListBuilder
     */
    public function setWebsiteCombinedPriceListBuilder($websiteCombinedPriceListBuilder)
    {
        $this->websiteCombinedPriceListBuilder = $websiteCombinedPriceListBuilder;
    }
}
