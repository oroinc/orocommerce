<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyFallbackAwareInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides base methods and initialize parameters for combined price builders
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractCombinedPriceListBuilder
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceListCollectionProvider
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var EntityRepository
     */
    protected $priceListToEntityRepository;

    /**
     * @var string
     */
    protected $priceListToEntityClassName;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var CombinedPriceListRepository
     */
    protected $fallbackRepository;

    /**
     * @var EntityRepository
     */
    protected $combinedPriceListToEntityRepository;

    /**
     * @var string
     */
    protected $combinedPriceListClassName;

    /**
     * @var string
     */
    protected $combinedPriceListToEntityClassName;

    /**
     * @var string
     */
    protected $fallbackClassName;

    /**
     * @var array
     */
    protected $builtList = [];

    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $scheduleResolver;

    /**
     * @var StrategyRegister
     */
    protected $strategyRegister;

    public function __construct(
        ManagerRegistry $registry,
        PriceListCollectionProvider $priceListCollectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListScheduleResolver $scheduleResolver,
        StrategyRegister $strategyRegister,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->priceListCollectionProvider = $priceListCollectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->scheduleResolver = $scheduleResolver;
        $this->strategyRegister = $strategyRegister;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param string $priceListToEntityClassName
     * @return $this
     */
    public function setPriceListToEntityClassName($priceListToEntityClassName)
    {
        $this->priceListToEntityClassName = $priceListToEntityClassName;

        return $this;
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

    /**
     * @param string $fallbackClassName
     * @return $this
     */
    public function setFallbackClassName($fallbackClassName)
    {
        $this->fallbackClassName = $fallbackClassName;

        return $this;
    }

    /**
     * @return EntityRepository
     */
    protected function getPriceListToEntityRepository()
    {
        if (!$this->priceListToEntityRepository) {
            $this->priceListToEntityRepository = $this->registry
                ->getManagerForClass($this->priceListToEntityClassName)
                ->getRepository($this->priceListToEntityClassName);
        }

        return $this->priceListToEntityRepository;
    }

    /**
     * @return EntityRepository
     */
    protected function getCombinedPriceListToEntityRepository()
    {
        if (!$this->combinedPriceListToEntityRepository) {
            $this->combinedPriceListToEntityRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListToEntityClassName)
                ->getRepository($this->combinedPriceListToEntityClassName);
        }

        return $this->combinedPriceListToEntityRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListClassName)
                ->getRepository($this->combinedPriceListClassName);
        }

        return $this->combinedPriceListRepository;
    }

    /**
     * @return EntityRepository
     */
    protected function getFallbackRepository()
    {
        if (!$this->fallbackRepository) {
            $this->fallbackRepository = $this->registry
                ->getManagerForClass($this->fallbackClassName)
                ->getRepository($this->fallbackClassName);
        }

        return $this->fallbackRepository;
    }

    /**
     * @return string
     */
    public function getCombinedPriceListToEntityClassName()
    {
        return $this->combinedPriceListToEntityClassName;
    }

    /**
     * @param string $combinedPriceListToEntityClassName
     */
    public function setCombinedPriceListToEntityClassName($combinedPriceListToEntityClassName)
    {
        $this->combinedPriceListToEntityClassName = $combinedPriceListToEntityClassName;
    }

    /**
     * @return $this
     */
    public function resetCache()
    {
        $this->builtList = [];

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function wrapInTransaction(callable $transactionalCallback)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass($this->combinedPriceListClassName);

        $em->beginTransaction();
        try {
            $transactionalCallback();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }
    }

    /**
     * @param CombinedPriceList $cpl
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     * @param int|null $forceTimestamp
     */
    protected function updateRelationsAndPrices(
        CombinedPriceList $cpl,
        Website $website,
        $targetEntity = null,
        $forceTimestamp = null
    ) {
        $relation = $this->getActiveCplRelation($cpl, $website, $targetEntity);
        if ($forceTimestamp !== null || !$relation->getPriceList()->isPricesCalculated()) {
            $this->strategyRegister->getCurrentStrategy()
                ->combinePrices($relation->getPriceList(), [], $forceTimestamp);
        }

        $this->processRelationTriggers($relation, $forceTimestamp);
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Website $website
     * @param array|PriceListSequenceMember[] $currentLevelPriceLists
     * @param array|PriceListSequenceMember[] $fallbackPriceLists
     * @param Customer|CustomerGroup|null $targetEntity
     * @param int|null $forceTimestamp
     */
    protected function updateRelationsAndPricesUsingFallback(
        CombinedPriceList $combinedPriceList,
        Website $website,
        array $currentLevelPriceLists,
        array $fallbackPriceLists,
        $targetEntity = null,
        $forceTimestamp = null
    ) {
        $fallbackLevelCpl = $this->combinedPriceListProvider->getCombinedPriceList($fallbackPriceLists);
        $strategy = $this->strategyRegister->getCurrentStrategy();

        if ($fallbackLevelCpl
            && $fallbackLevelCpl->isPricesCalculated()
            && $strategy instanceof PriceCombiningStrategyFallbackAwareInterface
        ) {
            $relation = $this->getActiveCplRelation($combinedPriceList, $website, $targetEntity);
            if ($forceTimestamp !== null || !$relation->getPriceList()->isPricesCalculated()) {
                $strategy->combinePricesUsingPrecalculatedFallback(
                    $combinedPriceList,
                    $currentLevelPriceLists,
                    $fallbackLevelCpl,
                    $forceTimestamp
                );
            }

            $this->processRelationTriggers($relation, $forceTimestamp);
        } else {
            // Update prices without using fallback if fallback CPL was not calculated yet
            $this->updateRelationsAndPrices($combinedPriceList, $website, $targetEntity, $forceTimestamp);
        }
    }

    /**
     * @return array
     */
    public function getBuiltList()
    {
        return $this->builtList;
    }

    /**
     * @param BaseCombinedPriceListRelation $relation
     * @param int|null $forceTimestamp
     */
    protected function processRelationTriggers(BaseCombinedPriceListRelation $relation, $forceTimestamp = null)
    {
        $hasOtherRelations = $this->getCombinedPriceListRepository()->hasOtherRelations($relation);
        //when CPL used the first time at this website
        if ($forceTimestamp !== null || !$hasOtherRelations) {
            $this->triggerHandler->process($relation->getPriceList(), $relation->getWebsite());
        }
    }

    /**
     * @param CombinedPriceList $cpl
     * @param Website $website
     * @param Customer|CustomerGroup|null $targetEntity
     * @return BaseCombinedPriceListRelation
     */
    protected function getActiveCplRelation(CombinedPriceList $cpl, Website $website, $targetEntity)
    {
        $activeCpl = $this->scheduleResolver->getActiveCplByFullCPL($cpl);
        if ($activeCpl === null) {
            $activeCpl = $cpl;
        }

        return $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($cpl, $activeCpl, $website, $targetEntity);
    }
}
