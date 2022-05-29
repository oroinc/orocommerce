<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Persistence\ManagerRegistry;
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
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var CombinedPriceListGarbageCollector */
    private $garbageCollector;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->garbageCollector = new CombinedPriceListGarbageCollector(
            $this->doctrine,
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
        $cplRepository->expects($this->once())
            ->method('removeDuplicatePrices');

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

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [CombinedPriceList::class, null, $cplRepository],
                [CombinedPriceListToCustomer::class, null, $customerRelationRepository],
                [CombinedPriceListToCustomerGroup::class, null, $customerGroupRelationRepository],
                [CombinedPriceListToWebsite::class, null, $websiteRelationRepository],
                [CombinedPriceListActivationRule::class, null, $cplActivationRuleRepository],
            ]);

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
