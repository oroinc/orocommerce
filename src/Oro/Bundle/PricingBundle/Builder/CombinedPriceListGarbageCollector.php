<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Psr\Log\LoggerInterface;

/**
 * Remove unused Combined Price Lists.
 * Combined Price List considered as unused when it is not associated with any entity and has no actual activation plan
 */
class CombinedPriceListGarbageCollector
{
    private CombinedPriceListTriggerHandler $triggerHandler;
    private ManagerRegistry $registry;
    private ConfigManager $configManager;
    private NativeQueryExecutorHelper $nativeQueryExecutorHelper;
    private LoggerInterface $logger;
    private int $logLevel = Logger::WARNING;
    private int $gcOffsetMinutes = 60;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        CombinedPriceListTriggerHandler $triggerHandler,
        NativeQueryExecutorHelper $nativeQueryExecutorHelper,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->triggerHandler = $triggerHandler;
        $this->nativeQueryExecutorHelper = $nativeQueryExecutorHelper;
        $this->logger = $logger;
    }

    public function setGcOffsetMinutes(int $offsetMinutes): void
    {
        $this->gcOffsetMinutes = $offsetMinutes;
    }

    public function setLogLevel(int $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    public function cleanCombinedPriceLists(array $cpls = []): void
    {
        $this->deleteInvalidRelations();
        $this->cleanActivationRules();
        $this->scheduleUnusedPriceListsRemoval();
        $this->removeDuplicatePrices($cpls);
    }

    private function deleteInvalidRelations(): void
    {
        $this->registry->getRepository(CombinedPriceListToCustomer::class)->deleteInvalidRelations();
        $this->registry->getRepository(CombinedPriceListToCustomerGroup::class)->deleteInvalidRelations();
        $this->registry->getRepository(CombinedPriceListToWebsite::class)->deleteInvalidRelations();
    }

    private function cleanActivationRules(): void
    {
        /** @var CombinedPriceListActivationRuleRepository $repo */
        $repo = $this->registry->getRepository(CombinedPriceListActivationRule::class);

        $repo->deleteExpiredRules(new \DateTime('now', new \DateTimeZone('UTC')));

        $exceptPriceLists = $this->getConfigFullChainPriceLists();
        $repo->deleteUnlinkedRules($exceptPriceLists);
    }

    private function scheduleUnusedPriceListsRemoval(): void
    {
        /** @var CombinedPriceListRepository $cplRepository */
        $cplRepository = $this->registry->getRepository(CombinedPriceList::class);
        $exceptPriceLists = $this->getAllConfigPriceLists();
        $cplRepository->scheduleUnusedPriceListsRemoval($this->nativeQueryExecutorHelper, $exceptPriceLists);
    }

    public function hasPriceListsScheduledForRemoval(): bool
    {
        /** @var CombinedPriceListRepository $cplRepository */
        $cplRepository = $this->registry->getRepository(CombinedPriceList::class);

        return $cplRepository->hasPriceListsScheduledForRemoval();
    }

    /**
     * Removes not actual at the moment CPLs that were previously requested for removal.
     * Clears all processed removal requests.
     *
     * CPLs that are actual but were requested previously will be not removed.
     */
    public function removeScheduledUnusedPriceLists(): void
    {
        /** @var CombinedPriceListRepository $cplRepository */
        $cplRepository = $this->registry->getRepository(CombinedPriceList::class);

        $exceptPriceLists = $this->getAllConfigPriceLists();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $minusOffsetTime = $now->sub(new \DateInterval(sprintf('PT%dM', $this->gcOffsetMinutes)));

        $priceListsForDelete = $cplRepository->getPriceListsScheduledForRemoval(
            $this->nativeQueryExecutorHelper,
            $minusOffsetTime,
            $exceptPriceLists
        );
        if (!$priceListsForDelete) {
            return;
        }

        $this->triggerHandler->startCollect();
        $this->triggerHandler->massProcess($priceListsForDelete);
        $cplRepository->deletePriceLists($priceListsForDelete);
        $this->triggerHandler->commit();

        $cplRepository->clearUnusedPriceListRemovalSchedule($minusOffsetTime);
    }

    private function getConfigFullChainPriceLists(): array
    {
        $exceptPriceLists = [];
        $configFullCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToFullPriceList());
        if ($configFullCombinedPriceList) {
            $exceptPriceLists[] = $configFullCombinedPriceList;
        }

        return $exceptPriceLists;
    }

    private function getConfigPriceLists(): array
    {
        $configCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $exceptPriceLists = [];
        if ($configCombinedPriceList) {
            $exceptPriceLists[] = $configCombinedPriceList;
        }

        return $exceptPriceLists;
    }

    private function getAllConfigPriceLists(): array
    {
        return array_merge(
            $this->getConfigPriceLists(),
            $this->getConfigFullChainPriceLists()
        );
    }

    private function removeDuplicatePrices(array $cpls = []): void
    {
        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->registry->getRepository(CombinedProductPrice::class);
        if ($repository->hasDuplicatePrices($cpls)) {
            $start = microtime(true);
            $removedRows = $repository->deleteDuplicatePrices($cpls);
            $end = microtime(true);
            if ($removedRows) {
                $this->logDuplicatePrices($end - $start, $removedRows);
            }
        }
    }

    private function logDuplicatePrices(float $time, int $removedRows): void
    {
        $time = \DateTime::createFromFormat('U.u', sprintf('%.6F', $time))->format('H:m:s');
        $message = sprintf('Removed %s duplicate combined prices. Spent time: %s.', $removedRows, $time);
        $this->logger->log($this->logLevel, $message);
    }
}
