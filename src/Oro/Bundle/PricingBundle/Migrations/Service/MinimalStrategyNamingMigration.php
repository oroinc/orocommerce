<?php

namespace Oro\Bundle\PricingBundle\Migrations\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelperInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;

/**
 * Migrate existing Combined Price Lists to optimized naming strategy when Minimal Pricing strategy used
 */
class MinimalStrategyNamingMigration
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var StrategyRegister
     */
    private $strategyRegistry;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    private $garbageCollector;

    /**
     * @var CombinedPriceListActivationPlanBuilder
     */
    private $activationPlanBuilder;

    /**
     * @var CombinedPriceListRelationHelperInterface
     */
    private $relationHelper;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $registry,
        StrategyRegister $strategyRegistry,
        CombinedPriceListGarbageCollector $garbageCollector,
        CombinedPriceListActivationPlanBuilder $activationPlanBuilder,
        CombinedPriceListRelationHelperInterface $relationHelper
    ) {
        $this->configManager = $configManager;
        $this->registry = $registry;
        $this->strategyRegistry = $strategyRegistry;
        $this->garbageCollector = $garbageCollector;
        $this->activationPlanBuilder = $activationPlanBuilder;
        $this->relationHelper = $relationHelper;
    }

    public function migrate()
    {
        $cplsByIdentifier = $this->getCplsByIdentifier();
        if (!$cplsByIdentifier) {
            return;
        }

        $configCPL = $this->configManager->get('oro_pricing.combined_price_list');
        $configFullCPL = $this->configManager->get('oro_pricing.full_combined_price_list');
        $hasConfigChanges = false;

        $manager = $this->registry->getManagerForClass(CombinedPriceList::class);
        /**
         * @var string $identifier
         * @var CombinedPriceList[] $cpls
         */
        foreach ($cplsByIdentifier as $identifier => $cpls) {
            $baseCpl = $this->getBaseCpl($cpls, $identifier);

            if ($baseCpl->getName() !== $identifier) {
                $baseCpl->setName($identifier);
                $manager->flush();
            }

            if (count($cpls) > 0) {
                $this->migrateRelations($baseCpl, $cpls, $manager);
                $hasConfigChanges = $hasConfigChanges
                    || $this->migrateConfiguration($baseCpl, $cpls, $configCPL, $configFullCPL);
            }
        }

        if ($hasConfigChanges) {
            $this->configManager->flush();
        }

        $this->rebuildActivationPlanRules();
        $this->garbageCollector->cleanCombinedPriceLists();
    }

    private function migrateRelations(CombinedPriceList $baseCpl, array $cpls, ObjectManager $manager): void
    {
        $relations = [
            CombinedPriceListToCustomer::class,
            CombinedPriceListToCustomerGroup::class,
            CombinedPriceListToWebsite::class
        ];
        foreach ($relations as $relation) {
            /** @var QueryBuilder $qb */
            $qb = $manager->getRepository($relation)->createQueryBuilder('e');
            $qb->update($relation, 'e')
                ->set('e.priceList', ':newPriceList')
                ->where($qb->expr()->in('e.priceList', ':duplicateCPLs'))
                ->setParameter('newPriceList', $baseCpl)
                ->setParameter('duplicateCPLs', $cpls);
            $qb->getQuery()->execute();

            /** @var QueryBuilder $qb */
            $qb = $manager->getRepository($relation)->createQueryBuilder('e');
            $qb->update($relation, 'e')
                ->set('e.fullChainPriceList', ':newPriceList')
                ->where($qb->expr()->in('e.fullChainPriceList', ':duplicateCPLs'))
                ->setParameter('newPriceList', $baseCpl)
                ->setParameter('duplicateCPLs', $cpls);
            $qb->getQuery()->execute();
        }
    }

    private function rebuildActivationPlanRules(): void
    {
        // Delete CPLs that are not connected to any entity and has no activation rules
        $this->garbageCollector->cleanCombinedPriceLists();

        // Delete all existing activation plans
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CombinedPriceListActivationRule::class);
        $em->createQueryBuilder()
            ->delete(CombinedPriceListActivationRule::class)
            ->getQuery()
            ->execute();

        // Create activation plans for the current list of Full CPLs
        $cplRepo = $this->registry
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);
        $qb = $cplRepo->createQueryBuilder('cpl');
        $iterator = new BufferedQueryResultIterator($qb);
        foreach ($iterator as $cpl) {
            if ($this->relationHelper->isFullChainCpl($cpl)) {
                $this->activationPlanBuilder->buildByCombinedPriceList($cpl);
            }
        }
    }

    /**
     * @param CombinedPriceList $baseCpl
     * @param array $cpls
     * @param int $configCPL
     * @param int $configFullCPL
     * @return bool
     */
    private function migrateConfiguration(CombinedPriceList $baseCpl, array $cpls, $configCPL, $configFullCPL): bool
    {
        $hasConfigChanges = false;
        foreach ($cpls as $cpl) {
            if ($cpl->getId() === $configCPL) {
                $hasConfigChanges = true;
                $this->configManager->set('oro_pricing.combined_price_list', $baseCpl->getId());
            }
            if ($cpl->getId() === $configFullCPL) {
                $hasConfigChanges = true;
                $this->configManager->set('oro_pricing.full_combined_price_list', $baseCpl->getId());
            }
        }

        return $hasConfigChanges;
    }

    private function getCplsByIdentifier(): array
    {
        $strategy = $this->strategyRegistry->getCurrentStrategy();
        if (!$strategy instanceof MinimalPricesCombiningStrategy) {
            return [];
        }

        /** @var CombinedPriceListRepository $cplRepo */
        $cplRepo = $this->registry
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);
        $qb = $cplRepo->createQueryBuilder('cpl');
        $iterator = new BufferedQueryResultIterator($qb);
        $cplsByIdentifier = [];
        foreach ($iterator as $cpl) {
            $identifier = $strategy->getCombinedPriceListIdentifier($cplRepo->getPriceListRelations($cpl));
            $cplsByIdentifier[$identifier][] = $cpl;
        }

        // Filter out already correct CPLs
        return array_filter(
            $cplsByIdentifier,
            static function ($cpls, $identifier) {
                return count($cpls) > 1
                    || reset($cpls)->getName() !== $identifier;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function getBaseCpl(array &$cpls, string $identifier): CombinedPriceList
    {
        $baseCpl = null;
        $cplWithIdentifierIdx = null;

        // Get first calculated with name same to new identifier
        foreach ($cpls as $i => $cpl) {
            if ($cpl->getName() === $identifier) {
                if ($cpl->isPricesCalculated()) {
                    $baseCpl = $cpl;
                    unset($cpls[$i]);
                    break;
                }

                $cplWithIdentifierIdx = $i;
            }
        }

        // If none get first calculated as base
        if (!$baseCpl) {
            foreach ($cpls as $i => $cpl) {
                if ($cpl->isPricesCalculated()) {
                    $baseCpl = $cpl;
                    unset($cpls[$i]);
                    break;
                }
            }
        }

        // If none get with same name as base
        if ($cplWithIdentifierIdx !== null) {
            $baseCpl = $cpls[$cplWithIdentifierIdx];
            unset($cpls[$cplWithIdentifierIdx]);
        }

        // If none get first as base
        if (!$baseCpl) {
            $baseCpl = reset($cpls);
            unset($cpls[0]);
        }

        return $baseCpl;
    }
}
