<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
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
    protected $garbageCollector;

    /**
     * @var array
     */
    protected $isBuilt = false;

    /**
     * @param ConfigManager $configManager
     * @param PriceListCollectionProvider $priceListCollectionProvider
     * @param CombinedPriceListProvider $combinedPriceListProvider
     * @param CombinedPriceListGarbageCollector $garbageCollector
     */
    public function __construct(
        ConfigManager $configManager,
        PriceListCollectionProvider $priceListCollectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListGarbageCollector $garbageCollector
    ) {
        $this->configManager = $configManager;
        $this->priceListCollectionProvider = $priceListCollectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->garbageCollector = $garbageCollector;
    }

    /**
     * @param WebsiteCombinedPriceListsBuilder $builder
     * @return $this
     */
    public function setWebsiteCombinedPriceListBuilder(WebsiteCombinedPriceListsBuilder $builder)
    {
        $this->websiteCombinedPriceListBuilder = $builder;

        return $this;
    }

    /**
     * @param boolean|false $force
     */
    public function build($force = false)
    {
        if ($force || !$this->isBuilt) {
            $this->updatePriceListsOnCurrentLevel($force);
            $this->websiteCombinedPriceListBuilder->build(null, $force);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->isBuilt = true;
        }
    }

    /**
     * @param boolean $force
     */
    protected function updatePriceListsOnCurrentLevel($force)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByConfig();
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $force);
        $this->updateCombinedPriceListConnection($actualCombinedPriceList);
    }

    /**
     * @param CombinedPriceList $priceList
     */
    protected function updateCombinedPriceListConnection(CombinedPriceList $priceList)
    {
        $configKey = Configuration::getConfigKeyToPriceList();
        if ((int)$this->configManager->get($configKey) !== $priceList->getId()) {
            $this->configManager->set($configKey, $priceList->getId());
            $this->configManager->flush();
        }
    }
}
