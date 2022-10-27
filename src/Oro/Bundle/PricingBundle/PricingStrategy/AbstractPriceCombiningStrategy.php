<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\QueryExecutorProviderInterface;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Abstract implementation of price combining strategy
 */
abstract class AbstractPriceCombiningStrategy implements PriceCombiningStrategyInterface
{
    protected ManagerRegistry $registry;
    protected QueryExecutorProviderInterface $queryExecutorProvider;
    protected ?EntityManagerInterface $manager = null;
    protected ?CombinedPriceListToPriceListRepository $combinedPriceListRelationsRepository = null;
    protected ?CombinedProductPriceRepository $combinedProductPriceRepository = null;
    protected bool $allowFallbackCplUsage = true;

    public function __construct(
        ManagerRegistry $registry,
        QueryExecutorProviderInterface $queryExecutorProvider
    ) {
        $this->registry = $registry;
        $this->queryExecutorProvider = $queryExecutorProvider;
    }

    public function getInsertSelectExecutor(): ShardQueryExecutorInterface
    {
        return $this->queryExecutorProvider->getQueryExecutor();
    }

    /**
     * This setter allows reconfiguring the strategy and disabling fallback CPL usage optimization.
     */
    public function setAllowFallbackCplUsage(bool $allowFallbackCplUsage): void
    {
        $this->allowFallbackCplUsage = $allowFallbackCplUsage;
    }

    public function combinePrices(CombinedPriceList $combinedPriceList, array $products = []): void
    {
        $priceListsRelations = $this->getCombinedPriceListRelationsRepository()
            ->getPriceListRelations(
                $combinedPriceList,
                $products
            );
        $combinedPriceRepository = $this->getCombinedProductPriceRepository();
        $combinedPriceRepository->deleteCombinedPrices($combinedPriceList, $products);

        // $fallbackCpl var assignment done within `if` statement to eliminate fallback CPL fetch
        // when $products is not empty.
        if ($this->isFallbackMergeAllowed($priceListsRelations)
            && ($fallbackCpl = $this->getFallbackCombinedPriceList($combinedPriceList))
        ) {
            $this->combinePricesUsingPrecalculatedFallback(
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
     * Combine prices using already calculated fallback combined price list.
     *
     *  - remove all prices for CPL
     *  - combine prices for price lists that are not included into fallback combined price list
     *  - add prices from combined price list
     */
    protected function combinePricesUsingPrecalculatedFallback(
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
        $this->processCombinedPriceListRelation($combinedPriceList, $fallbackCpl, $products);
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

    /**
     * Merge prices from a fallback CPL on top of prices in the combined price list.
     * Merge may be done for a limited set of products.
     */
    abstract protected function processCombinedPriceListRelation(
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
     */
    abstract protected function processPriceLists(
        CombinedPriceList $combinedPriceList,
        array $priceListRelations,
        array $products = []
    ): void;

    protected function getManager(): EntityManagerInterface
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManagerForClass(CombinedPriceList::class);
        }

        return $this->manager;
    }

    protected function getCombinedPriceListRelationsRepository(): CombinedPriceListToPriceListRepository
    {
        if (!$this->combinedPriceListRelationsRepository) {
            $this->combinedPriceListRelationsRepository = $this->registry
                ->getRepository(CombinedPriceListToPriceList::class);
        }

        return $this->combinedPriceListRelationsRepository;
    }

    protected function getCombinedProductPriceRepository(): CombinedProductPriceRepository
    {
        if (!$this->combinedProductPriceRepository) {
            $this->combinedProductPriceRepository = $this->registry->getRepository(CombinedProductPrice::class);
        }

        return $this->combinedProductPriceRepository;
    }
}
