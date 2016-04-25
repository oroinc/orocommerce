<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;

class CombinedPriceListsBuilder
{
    const DEFAULT_OFFSET_OF_PROCESSING_CPL_PRICES = 12;

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
     * @param int|null $behavior
     * @param bool $farce
     */
    public function build($behavior = null, $farce = false)
    {
        if (!$this->isBuilt) {
            $this->updatePriceListsOnCurrentLevel($behavior);
            $this->websiteCombinedPriceListBuilder->build(null, $behavior, $farce);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->isBuilt = true;
        }
    }

    /**
     * @param boolean $behavior
     */
    protected function updatePriceListsOnCurrentLevel($behavior)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByConfig();
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $behavior);
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
