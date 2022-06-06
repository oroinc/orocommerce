<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use PHPUnit\Framework\MockObject\MockObject;

class CombinedPriceListGarbageCollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var MockObject|Registry
     */
    protected $registry;

    /**
     * @var MockObject|CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var NativeQueryExecutorHelper|MockObject
     */
    protected $nativeQueryExecutorHelper;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $garbageCollector;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->registry = $this->createMock(Registry::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->nativeQueryExecutorHelper = $this->createMock(NativeQueryExecutorHelper::class);

        $this->garbageCollector = new CombinedPriceListGarbageCollector(
            $this->registry,
            $this->configManager,
            $this->triggerHandler
        );
        $this->garbageCollector->setNativeQueryExecutorHelper($this->nativeQueryExecutorHelper);
    }

    public function testCleanCombinedPriceLists()
    {
        $this->assertConfigManagerCalls();

        $cplRepository = $this->createMock(CombinedPriceListRepository::class);
        $cplRepository->expects($this->once())
            ->method('scheduleUnusedPriceListsRemoval')
            ->with($this->nativeQueryExecutorHelper, [1, 2]);

        $customerRelationRepository = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $customerRelationRepository->expects($this->once())
            ->method('deleteInvalidRelations');
        $customerGroupRelationRepository = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $customerGroupRelationRepository->expects($this->once())
            ->method('deleteInvalidRelations');
        $websiteRelationRepository = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRelationRepository->expects($this->once())
            ->method('deleteInvalidRelations');

        $cplActivationRuleRepository = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $cplActivationRuleRepository->expects($this->once())
            ->method('deleteExpiredRules')
            ->with($this->isInstanceOf(\DateTime::class));
        $cplActivationRuleRepository->expects($this->once())
            ->method('deleteUnlinkedRules')
            ->with([2]);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceList::class, null, $cplRepository],
                [CombinedPriceListToCustomer::class, null, $customerRelationRepository],
                [CombinedPriceListToCustomerGroup::class, null, $customerGroupRelationRepository],
                [CombinedPriceListToWebsite::class, null, $websiteRelationRepository],
                [CombinedPriceListActivationRule::class, null, $cplActivationRuleRepository],
            ]);

        $this->garbageCollector->cleanCombinedPriceLists();
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
