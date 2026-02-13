<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This provider returns dependent price lists for given price list
 */
class DependentPriceListProvider
{
    private ManagerRegistry $doctrine;
    private CacheInterface $cache;

    /** @var PriceRuleLexemeTriggerHandler */
    protected $priceRuleLexemeTriggerHandler;

    public function __construct(PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler)
    {
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
    }

    public function setManagerRegistry(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @param PriceList $priceList
     * @return array|PriceList[]
     */
    public function getDependentPriceLists(PriceList $priceList)
    {
        return $this->loadDependentPriceLists($priceList, false);
    }

    public function getResolvedOrderedDependencies(int $priceListId): array
    {
        return $this->cache->get('pl_deps_' . $priceListId, function () use ($priceListId) {
            return $this->kahnByWaves(...$this->buildDependencyGraph($priceListId));
        });
    }

    /**
     * @param PriceList $priceList
     * @return array|PriceList[]
     */
    public function getDirectlyDependentPriceLists(PriceList $priceList): array
    {
        return $this->loadDependentPriceLists($priceList, true);
    }

    private function loadDependentPriceLists(PriceList $priceList, bool $onlyDirect = false): array
    {
        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            [],
            $priceList->getId()
        );

        $dependentPriceLists = [];
        foreach ($lexemes as $lexeme) {
            $dependentPriceList = $lexeme->getPriceList();
            if ($dependentPriceList) {
                $dependentPriceLists[$dependentPriceList->getId()] = $dependentPriceList;
                if (!$onlyDirect) {
                    foreach ($this->getDependentPriceLists($dependentPriceList) as $subDependentPriceList) {
                        $dependentPriceLists[$subDependentPriceList->getId()] = $subDependentPriceList;
                    }
                }
            }
        }

        return $dependentPriceLists;
    }

    /**
     * @param iterable|PriceList[] $priceLists
     * @return PriceList[]
     */
    public function appendDependent($priceLists)
    {
        $priceListsWithDependent = [];
        foreach ($priceLists as $priceList) {
            $priceListsWithDependent[$priceList->getId()] = $priceList;
            foreach ($this->getDependentPriceLists($priceList) as $dependentPriceList) {
                $priceListsWithDependent[$dependentPriceList->getId()] = $dependentPriceList;
            }
        }

        return $priceListsWithDependent;
    }

    /**
     * Prepare the dependency graph and in-degree array for Kahn's algorithm.
     */
    private function buildDependencyGraph(
        int $priceListId
    ): array {
        $graph = [];
        $inDegree = [];
        $visited = [];

        $queue = new \SplQueue();
        $queue->enqueue($priceListId);
        $visited[$priceListId] = true;
        $inDegree[$priceListId] ??= 0;

        $em = $this->doctrine->getManagerForClass(PriceList::class);
        while (!$queue->isEmpty()) {
            $currentId = $queue->dequeue();
            $dependents = $this->getDirectlyDependentPriceLists(
                $em->getReference(PriceList::class, $currentId)
            );

            foreach ($dependents as $dependentPriceList) {
                $dependentId = $dependentPriceList->getId();
                $graph[$currentId][] = $dependentId;

                $inDegree[$dependentId] = ($inDegree[$dependentId] ?? 0) + 1;
                $inDegree[$currentId] ??= 0;

                if (!isset($visited[$dependentId])) {
                    $visited[$dependentId] = true;
                    $queue->enqueue($dependentId);
                }
            }
        }

        return [$graph, $inDegree];
    }

    /**
     * Sorts the price lists by dependency order using Kahn's algorithm.
     */
    private function kahnByWaves(array $graph, array $inDegree): array
    {
        $waves = [];
        $currentWave = [];

        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $currentWave[] = $node;
            }
        }

        $processedCount = 0;
        while (!empty($currentWave)) {
            $waves[] = $currentWave;
            $nextWave = [];

            foreach ($currentWave as $node) {
                $processedCount++;

                foreach ($graph[$node] ?? [] as $dependent) {
                    $inDegree[$dependent]--;

                    if ($inDegree[$dependent] === 0) {
                        $nextWave[] = $dependent;
                    }
                }
            }

            $currentWave = $nextWave;
        }

        if ($processedCount !== count($inDegree)) {
            throw new \RuntimeException('Circular dependency detected.');
        }

        return $waves;
    }
}
