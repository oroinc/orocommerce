<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectExecutorAwareInterface;
use Oro\Bundle\PricingBundle\ORM\QueryExecutorProviderInterface;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract implementation of price combining strategy
 *
 * Complexity raised because of BC layer. In 5.1 this class was refactored and complexity lowered.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractPriceCombiningStrategy implements
    PriceCombiningStrategyInterface,
    InsertFromSelectExecutorAwareInterface,
    PriceCombiningStrategyFallbackAwareInterface
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var CombinedPriceListToPriceListRepository
     */
    protected $combinedPriceListRelationsRepository;

    /**
     * @var CombinedProductPriceRepository
     */
    protected $combinedProductPriceRepository;
    /**
     * @var array
     */
    protected $builtList = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var QueryExecutorProviderInterface
     */
    protected $queryExecutorProvider;

    /**
     * @var bool
     */
    protected $allowFallbackCplUsage = true;

    /**
     * @var bool
     */
    private $isInsertFromSelectExecutorChanged = false;

    public function __construct(
        Registry $registry,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->triggerHandler = $triggerHandler;
    }

    public function setQueryExecutorProvider(QueryExecutorProviderInterface $queryExecutorProvider): void
    {
        $this->queryExecutorProvider = $queryExecutorProvider;
    }

    /**
     * This setter allows reconfiguring the strategy and disabling fallback CPL usage optimization.
     */
    public function setAllowFallbackCplUsage(bool $allowFallbackCplUsage): void
    {
        $this->allowFallbackCplUsage = $allowFallbackCplUsage;
    }

    /**
     * @deprecated Console binding will be removed in 5.1
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return bool
     * @deprecated Console binding will be removed in 5.1
     */
    protected function isOutputEnabled()
    {
        return $this->output !== null && $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
    }

    public function combinePrices(CombinedPriceList $combinedPriceList, array $products = [], $startTimestamp = null)
    {
        if (!$products
            && $startTimestamp !== null
            && !empty($this->builtList[$startTimestamp][$combinedPriceList->getId()])
        ) {
            //this CPL was recalculated at this go
            return;
        }

        $this->combinePricesWithoutTriggers($combinedPriceList, $products);

        $this->triggerHandler->processByProduct($combinedPriceList, $products);
        $this->builtList[$startTimestamp][$combinedPriceList->getId()] = true;
    }

    /**
     * @internal
     * Compatibility version of combinePrices that does not trigger indexation events.
     * Will replace combinePrices in 5.1
     */
    public function combinePricesWithoutTriggers(
        CombinedPriceList $combinedPriceList,
        array $products = []
    ) {
        $priceListsRelations = $this->getCombinedPriceListRelationsRepository()
            ->getPriceListRelations(
                $combinedPriceList,
                $products
            );

        $progressBar = null;
        if ($this->isOutputEnabled()) {
            $this->output->writeln(
                sprintf(
                    'Processing combined price list id: %s - %s',
                    $combinedPriceList->getId(),
                    $combinedPriceList->getName()
                ),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $progressBar = new ProgressBar($this->output, \count($priceListsRelations));
        }
        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList, $products);

        // $fallbackCpl var assignment done within `if` statement to eliminate fallback CPL fetch
        // when $products is not empty.
        if ($this->isFallbackMergeAllowed($priceListsRelations)
            && ($fallbackCpl = $this->getFallbackCombinedPriceList($combinedPriceList))
        ) {
            $this->combinePricesUsingPrecalculatedFallbackWithProducts(
                $combinedPriceList,
                $fallbackCpl,
                $priceListsRelations,
                $products
            );
        } else {
            $this->combinePricesForAllPriceLists($combinedPriceList, $priceListsRelations, $products);
        }

        if (!$products) {
            $combinedPriceList->setPricesCalculated(true);
            $this->getManager()->flush($combinedPriceList);
        }

        if ($this->isOutputEnabled()) {
            $progressBar->finish();
            $this->output->writeln(
                '<info> - Finished processing combined price list id: ' . $combinedPriceList->getId() . '</info>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
    }

    /**
     * @deprecated Usage of this method now deprecated, prefer combinePricesUsingPrecalculatedFallbackWithProducts
     *
     * After 5.1 combinePricesUsingPrecalculatedFallbackWithProducts
     * will be renamed to combinePricesUsingPrecalculatedFallback
     */
    public function combinePricesUsingPrecalculatedFallback(
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        CombinedPriceList $fallbackLevelCpl,
        $startTimestamp = null
    ) {
        if ($startTimestamp !== null
            && !empty($this->builtList[$startTimestamp][$combinedPriceList->getId()])
        ) {
            //this CPL was recalculated at this go
            return;
        }
        // CPL cannot be fallback for itself, just skip, no calculations required.
        // Example. Minimal strategy. Fallback is 1,2 (CPL 1_2), Current CPL is 1,2,1 (same CPL 1_2)
        if ($combinedPriceList->getId() === $fallbackLevelCpl->getId()) {
            return;
        }

        $progressBar = null;
        if ($this->isOutputEnabled()) {
            $this->output->writeln(
                sprintf(
                    'Processing combined price list id: %d - %s',
                    $combinedPriceList->getId(),
                    $combinedPriceList->getName()
                ),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $progressBar = new ProgressBar($this->output, \count($priceLists) + 1);
        }

        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList);

        $priceListRelations = $this->getPriceListRelationsBySequenceMembers($combinedPriceList, $priceLists);
        $this->processPriceLists($combinedPriceList, $priceListRelations, [], $progressBar);

        if ($this->isOutputEnabled()) {
            $progressBar->finish();
            $progressBar->clear();
            $this->output->writeln(
                sprintf(
                    'Applying combined price: %d - %s',
                    $fallbackLevelCpl->getId(),
                    $fallbackLevelCpl->getName()
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
            $progressBar->display();
        }
        $this->processCombinedPriceListRelation($combinedPriceList, $fallbackLevelCpl);

        $combinedPriceList->setPricesCalculated(true);
        $this->getManager()->flush($combinedPriceList);

        if ($this->isOutputEnabled()) {
            $progressBar->finish();
            $this->output->writeln(
                '<info> - Finished processing combined price list id: ' . $combinedPriceList->getId() . '</info>',
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $this->triggerHandler->processByProduct($combinedPriceList);
        $this->builtList[$startTimestamp][$combinedPriceList->getId()] = true;
    }

    /**
     * Combine prices using already calculated fallback combined price list.
     *
     *  - remove all prices for CPL
     *  - combine prices for price lists that are not included into fallback combined price list
     *  - add prices from combined price list
     */
    protected function combinePricesUsingPrecalculatedFallbackWithProducts(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $fallbackCpl,
        array $priceListsRelations,
        array $products = []
    ): void {
        // CPL cannot be fallback for itself, just skip, no calculations required.
        if ($combinedPriceList->getId() === $fallbackCpl->getId()) {
            return;
        }

        $tailPriceListRelations = $this->getPriceListRelationsNotIncludedInFallback(
            $priceListsRelations,
            $this->getCombinedPriceListRelationsRepository()->getPriceListRelations($fallbackCpl, $products)
        );
        if (count($tailPriceListRelations) > 0) {
            $this->processPriceLists($combinedPriceList, $tailPriceListRelations, $products);
        }
        $this->processCombinedPriceListRelationWithProducts($combinedPriceList, $fallbackCpl, $products);
    }

    /**
     * Check if fallback CPL optimization may be used for a given price lists chain.
     *
     * No need to use fallback when there are only 2 PLs in the chain because number of operations with fallback
     * will also equal to 2.
     */
    protected function isFallbackMergeAllowed(array $relationsCollection): bool
    {
        return $this->allowFallbackCplUsage && count($relationsCollection) > 2;
    }

    /**
     * Combine prices for a given combined price list and optional products.
     *
     *  - remove all prices for CPL
     *  - combine prices for price lists that included into combined price list if any
     */
    protected function combinePricesForAllPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceListsRelations,
        array $products = []
    ): void {
        if (count($priceListsRelations) > 0) {
            $this->processPriceLists($combinedPriceList, $priceListsRelations, $products);
        }
    }

    /**
     * Return the best matching fallback Combined Price List for a give Combined Price List.
     *
     * Best matching fallback is a Combined Price List that includes maximum number of price lists in the chain that may
     * be reused during price lists combination process. This CPL may differ for different strategies because of
     * the internal merge logic.
     */
    abstract protected function getFallbackCombinedPriceList(CombinedPriceList $combinedPriceList): ?CombinedPriceList;

    /**
     * Returns price list relations that should be merged with fallback CPL.
     *
     * Mostly these are relations that are not included into fallback CPL relations.
     * But list may be changed based on the internal strategy logic
     */
    abstract protected function getPriceListRelationsNotIncludedInFallback(
        array $combinedPriceListRelation,
        array $fallbackCplRelations
    ): array;

    public function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $relatedCombinedPriceList
    ) {
        $this->processCombinedPriceListRelationWithProducts($combinedPriceList, $relatedCombinedPriceList);
    }

    /**
     * Merge prices from a fallback CPL on top of prices in the combined price list.
     * Merge may be done for a limited set of products.
     */
    abstract protected function processCombinedPriceListRelationWithProducts(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $fallbackCpl,
        array $products = []
    ): void;

    /**
     * Merge prices from a given price lists into a combined price list. Merge may be done for a limited set of products
     *
     * @param CombinedPriceList $combinedPriceList
     * @param array|CombinedPriceListToPriceList[] $priceListRelations
     * @param array|Product[] $products
     * @param ProgressBar|null $progressBar
     */
    abstract protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceListRelations,
        array $products = [],
        ProgressBar $progressBar = null
    );

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManagerForClass(CombinedPriceList::class);
        }

        return $this->manager;
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getCombinedPriceListRelationsRepository()
    {
        if (!$this->combinedPriceListRelationsRepository) {
            $this->combinedPriceListRelationsRepository = $this->registry
                ->getRepository(CombinedPriceListToPriceList::class);
        }

        return $this->combinedPriceListRelationsRepository;
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getCombinedProductPriceRepository()
    {
        if (!$this->combinedProductPriceRepository) {
            $this->combinedProductPriceRepository = $this->registry->getRepository(CombinedProductPrice::class);
        }

        return $this->combinedProductPriceRepository;
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
     * Apply prices from single price list relation into a given combined price list.
     * This method will be removed from abstract class in 5.1
     */
    abstract protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products = []
    );

    /**
     * @deprecated Will be removed in 5.1
     */
    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor)
    {
        $this->isInsertFromSelectExecutorChanged = true;
        $this->insertFromSelectQueryExecutor = $queryExecutor;
    }

    /**
     * @return ShardQueryExecutorInterface
     */
    public function getInsertSelectExecutor()
    {
        // BC layer. Will be removed in 5.1
        if ($this->isInsertFromSelectExecutorChanged) {
            return $this->insertFromSelectQueryExecutor;
        }

        return $this->queryExecutorProvider->getQueryExecutor();
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|PriceListSequenceMember[] $priceLists
     * @return array
     */
    protected function getPriceListRelationsBySequenceMembers(
        CombinedPriceList $combinedPriceList,
        array $priceLists
    ): array {
        $priceListRelations = [];
        foreach ($priceLists as $key => $sequenceMember) {
            $relation = new CombinedPriceListToPriceList();
            $relation->setCombinedPriceList($combinedPriceList);
            $relation->setPriceList($sequenceMember->getPriceList());
            $relation->setMergeAllowed($sequenceMember->isMergeAllowed());
            $relation->setSortOrder($key);

            $priceListRelations[] = $relation;
        }

        return $priceListRelations;
    }

    /**
     * @deprecated Console binding will be removed in 5.1
     */
    protected function moveProgress(
        ?ProgressBar $progressBar,
        int &$progress,
        CombinedPriceListToPriceList $priceListRelation
    ): void {
        if ($this->isOutputEnabled()) {
            if ($progressBar) {
                $progressBar->setProgress(++$progress);
                $progressBar->clear();
            }
            $this->output->writeln(
                'Processing price list: ' . $priceListRelation->getPriceList()->getName(),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
            if ($progressBar) {
                $progressBar->display();
            }
        }
    }
}
