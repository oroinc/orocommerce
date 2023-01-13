<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CombinedPriceListGarbageCollectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CombinedPriceListGarbageCollector */
    private $garbageCollector;

    /** @var NativeQueryExecutorHelper|MockObject */
    private $nativeQueryExecutorHelper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->nativeQueryExecutorHelper = $this->createMock(NativeQueryExecutorHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->garbageCollector = new CombinedPriceListGarbageCollector(
            $this->registry,
            $this->configManager,
            $this->triggerHandler,
            $this->nativeQueryExecutorHelper,
            $this->logger
        );
    }

    public function testCleanCombinedPriceLists()
    {
        $this->assertConfigManagerCalls();
        [$combinedProductPriceRepository] = $this->assertRepositories();
        $combinedProductPriceRepository
            ->expects($this->once())
            ->method('hasDuplicatePrices')
            ->willReturn(false);
        $combinedProductPriceRepository
            ->expects($this->never())
            ->method('deleteDuplicatePrices');

        $this->logger
            ->expects($this->never())
            ->method('log');

        $this->garbageCollector->cleanCombinedPriceLists([1]);
    }

    public function testCleanCombinedPriceListsWithLog(): void
    {
        [$combinedProductPriceRepository] = $this->assertRepositories();
        $this->assertConfigManagerCalls();

        $combinedProductPriceRepository
            ->expects($this->any())
            ->method('hasDuplicatePrices')
            ->willReturn(true);
        $combinedProductPriceRepository
            ->expects($this->any())
            ->method('deleteDuplicatePrices')
            ->with([1])
            ->willReturn(5);

        $this->logger
            ->expects($this->once())
            ->method('log');

        $this->garbageCollector->cleanCombinedPriceLists([1]);
    }

    private function assertRepositories(): array
    {
        $cplRepository = $this->createMock(CombinedPriceListRepository::class);
        $cplRepository
            ->expects($this->once())
            ->method('scheduleUnusedPriceListsRemoval')
            ->with($this->nativeQueryExecutorHelper, [1, 2]);

        $customerRelationRepository = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $customerRelationRepository
            ->expects($this->once())
            ->method('deleteInvalidRelations');

        $customerGroupRelationRepository = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $customerGroupRelationRepository
            ->expects($this->once())
            ->method('deleteInvalidRelations');

        $websiteRelationRepository = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRelationRepository
            ->expects($this->once())
            ->method('deleteInvalidRelations');

        $cplActivationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $cplActivationRuleRepository
            ->expects($this->once())
            ->method('deleteExpiredRules')
            ->with($this->isInstanceOf(\DateTime::class));

        $cplActivationRuleRepository
            ->expects($this->once())
            ->method('deleteUnlinkedRules')
            ->with([2]);

        $combinedProductPriceRepository = $this->createMock(CombinedProductPriceRepository::class);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceList::class, null, $cplRepository],
                [CombinedProductPrice::class, null, $combinedProductPriceRepository],
                [CombinedPriceListToCustomer::class, null, $customerRelationRepository],
                [CombinedPriceListToCustomerGroup::class, null, $customerGroupRelationRepository],
                [CombinedPriceListToWebsite::class, null, $websiteRelationRepository],
                [CombinedPriceListActivationRule::class, null, $cplActivationRuleRepository],
            ]);

        return [
            $combinedProductPriceRepository,
            $cplRepository,
            $customerRelationRepository,
            $customerGroupRelationRepository,
            $websiteRelationRepository,
            $cplActivationRuleRepository
        ];
    }

    public function testHasPriceListsScheduledForRemoval()
    {
        $cplRepository = $this->createMock(CombinedPriceListRepository::class);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(CombinedPriceList::class)
            ->willReturn($cplRepository);
        $cplRepository->expects($this->once())
            ->method('hasPriceListsScheduledForRemoval')
            ->willReturn(true);

        $this->assertTrue($this->garbageCollector->hasPriceListsScheduledForRemoval());
    }

    public function testRemoveScheduledUnusedPriceLists()
    {
        $cplRepository = $this->createMock(CombinedPriceListRepository::class);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(CombinedPriceList::class)
            ->willReturn($cplRepository);

        $this->assertConfigManagerCalls();
        $invalidCPLs = [42, 45];

        $cplRepository->expects($this->once())
            ->method('getPriceListsScheduledForRemoval')
            ->with($this->nativeQueryExecutorHelper, $this->isInstanceOf(\DateTimeInterface::class), [1, 2])
            ->willReturn($invalidCPLs);
        $cplRepository->expects($this->once())
            ->method('deletePriceLists')
            ->with($invalidCPLs);

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('massProcess')
            ->with($invalidCPLs);
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->garbageCollector->removeScheduledUnusedPriceLists();
    }

    private function assertConfigManagerCalls(): void
    {
        $this->configManager->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['oro_pricing.combined_price_list', false, false, null, 1],
                ['oro_pricing.full_combined_price_list', false, false, null, 2]
            ]);
    }
}
