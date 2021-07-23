<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

/**
 * Builder for combined price lists.
 * Perform CPL build for config level, call website CPL builder for websites with fallback to config.
 *
 * @internal Allowed to be accessed only by CombinedPriceListsBuilderFacade
 */
class CombinedPriceListsBuilder
{
    const DEFAULT_OFFSET_OF_PROCESSING_CPL_PRICES = 12.0;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $combinedPriceListClassName;

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
     * @var CombinedPriceListScheduleResolver
     */
    protected $scheduleResolver;

    /**
     * @var StrategyRegister
     */
    protected $priceStrategyRegister;

    /**
     * @var bool
     */
    protected $built = false;

    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    public function __construct(
        Registry $registry,
        ConfigManager $configManager,
        PriceListCollectionProvider $priceListCollectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListScheduleResolver $scheduleResolver,
        StrategyRegister $priceStrategyRegister,
        CombinedPriceListTriggerHandler $triggerHandler,
        WebsiteCombinedPriceListsBuilder $builder
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->priceListCollectionProvider = $priceListCollectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->scheduleResolver = $scheduleResolver;
        $this->priceStrategyRegister = $priceStrategyRegister;
        $this->triggerHandler = $triggerHandler;
        $this->websiteCombinedPriceListBuilder = $builder;
    }

    /**
     * @param int|null $forceTimestamp
     */
    public function build($forceTimestamp = null)
    {
        if (!$this->isBuilt()) {
            /** @var EntityManagerInterface $em */
            $em = $this->registry->getManagerForClass(
                $this->combinedPriceListClassName
            );

            $isChangesCommittedToEm = false;
            $this->triggerHandler->startCollect();
            $em->beginTransaction();
            try {
                $this->updatePriceListsOnCurrentLevel($forceTimestamp);
                $em->commit();
                $isChangesCommittedToEm = true;

                // build for websites with fallback to config
                $this->websiteCombinedPriceListBuilder->build(null, $forceTimestamp);
                $this->triggerHandler->commit();
                $this->built = true;
            } catch (\Exception $e) {
                $this->triggerHandler->rollback();

                if (false === $isChangesCommittedToEm) {
                    $em->rollback();
                }

                throw $e;
            }
        }
    }

    /**
     * @param int|null $forceTimestamp
     */
    protected function updatePriceListsOnCurrentLevel($forceTimestamp = null)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByConfig();
        $fullCpl = $this->combinedPriceListProvider->getCombinedPriceList($collection);
        $this->updateCombinedPriceListConnection($fullCpl, $forceTimestamp);
    }

    /**
     * @param CombinedPriceList $cpl
     * @param int|null $forceTimestamp
     */
    protected function updateCombinedPriceListConnection(CombinedPriceList $cpl, $forceTimestamp = null)
    {
        $activeCpl = $this->scheduleResolver->getActiveCplByFullCPL($cpl);
        if ($activeCpl === null) {
            $activeCpl = $cpl;
        }
        if ($forceTimestamp !== null || !$activeCpl->isPricesCalculated()) {
            $this->priceStrategyRegister->getCurrentStrategy()->combinePrices($activeCpl, [], $forceTimestamp);
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
        $this->built = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBuilt()
    {
        return $this->built;
    }

    /**
     * @param string $combinedPriceListClassName
     * @return $this
     */
    public function setCombinedPriceListClassName($combinedPriceListClassName)
    {
        $this->combinedPriceListClassName = $combinedPriceListClassName;

        return $this;
    }
}
