<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;

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
     * @var CombinedPriceListScheduleResolver
     */
    protected $scheduleResolver;

    /**
     * @var CombinedProductPriceResolver
     */
    protected $priceResolver;

    /**
     * @var array
     */
    protected $isBuilt = false;

    /**
     * @param ConfigManager $configManager
     * @param PriceListCollectionProvider $priceListCollectionProvider
     * @param CombinedPriceListProvider $combinedPriceListProvider
     * @param CombinedPriceListGarbageCollector $garbageCollector
     * @param CombinedPriceListScheduleResolver $scheduleResolver
     * @param CombinedProductPriceResolver $priceResolver
     */
    public function __construct(
        ConfigManager $configManager,
        PriceListCollectionProvider $priceListCollectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListGarbageCollector $garbageCollector,
        CombinedPriceListScheduleResolver $scheduleResolver,
        CombinedProductPriceResolver $priceResolver
    ) {
        $this->configManager = $configManager;
        $this->priceListCollectionProvider = $priceListCollectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->garbageCollector = $garbageCollector;
        $this->scheduleResolver = $scheduleResolver;
        $this->priceResolver = $priceResolver;
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
     * @param bool $force
     */
    public function build($force = false)
    {
        if (!$this->isBuilt) {
            $this->updatePriceListsOnCurrentLevel($force);
            $this->websiteCombinedPriceListBuilder->build(null, $force);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->isBuilt = true;
        }
    }

    /**
     * @param bool $force
     */
    protected function updatePriceListsOnCurrentLevel($force)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByConfig();
        $fullCpl = $this->combinedPriceListProvider->getCombinedPriceList($collection);
        $this->updateCombinedPriceListConnection($fullCpl, $force);
    }

    /**
     * @param CombinedPriceList $cpl
     * @param bool $force
     */
    protected function updateCombinedPriceListConnection(CombinedPriceList $cpl, $force = false)
    {
        $activeCpl = $this->scheduleResolver->getActiveCplByFullCPL($cpl);
        if ($activeCpl === null) {
            $activeCpl = $cpl;
        }
        if ($force || !$activeCpl->isPricesCalculated()) {
            $this->priceResolver->combinePrices($activeCpl);
        }
        $actualCplConfigKey = Configuration::getConfigKeyToPriceList();
        $fullCplConfigKey = Configuration::getConfigKeyToFullPriceList();
        $hasChanged = false;
        if ((int)$this->configManager->get($fullCplConfigKey) !== $cpl->getId()) {
            $this->configManager->set($fullCplConfigKey, $cpl->getId());
            $hasChanged = true;
        }
        if ((int)$this->configManager->get($actualCplConfigKey) !== $activeCpl->getId()) {
            $this->configManager->set($actualCplConfigKey, $activeCpl->getId());
            $hasChanged = true;
        }
        if ($hasChanged) {
            $this->configManager->flush();
        }
    }

    /**
     * @return $this
     */
    public function resetCache()
    {
        $this->isBuilt = false;

        return $this;
    }
}
