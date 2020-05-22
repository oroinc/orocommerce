<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

class CombinedPriceListGarbageCollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $garbageCollector;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->registry = $this->createMock(Registry::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->garbageCollector = new CombinedPriceListGarbageCollector(
            $this->registry,
            $this->configManager,
            $this->triggerHandler
        );
    }

    public function testCleanCombinedPriceLists()
    {
        $this->configManager->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['oro_pricing.combined_price_list', false, false, null, 1],
                ['oro_pricing.full_combined_price_list', false, false, null, 2]
            ]);

        $invalidCPLs = [42, 45];
        $cplRepository = $this->createMock(CombinedPriceListRepository::class);
        $cplRepository->expects($this->once())
            ->method('getUnusedPriceListsIds')
            ->with([1, 2])
            ->willReturn($invalidCPLs);
        $cplRepository->expects($this->once())
            ->method('deletePriceLists')
            ->with($invalidCPLs);

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

        $em = $this->createMock(EntityManager::class);
        $em->method('getRepository')
            ->willReturnMap([
                [CombinedPriceList::class, $cplRepository],
                [CombinedPriceListToCustomer::class, $customerRelationRepository],
                [CombinedPriceListToCustomerGroup::class, $customerGroupRelationRepository],
                [CombinedPriceListToWebsite::class, $websiteRelationRepository],
                [CombinedPriceListActivationRule::class, $cplActivationRuleRepository],
            ]);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $this->triggerHandler->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('massProcess')
            ->with($invalidCPLs);
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->garbageCollector->cleanCombinedPriceLists();
    }
}
