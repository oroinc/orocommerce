<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class CombinedPriceListScheduleResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var CombinedPriceListScheduleResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->resolver = new CombinedPriceListScheduleResolver(
            $this->registry,
            $this->configManager,
            $this->triggerHandler
        );
        $this->resolver->addRelationClass(CombinedPriceListToCustomer::class);
        $this->resolver->addRelationClass(CombinedPriceListToCustomerGroup::class);
        $this->resolver->addRelationClass(CombinedPriceListToWebsite::class);
    }

    public function testUpdateRelations()
    {
        $actualityPerRepo = [
            CombinedPriceListToCustomerGroupRepository::class => 0,
            CombinedPriceListToCustomerRepository::class => 0,
            CombinedPriceListToWebsiteRepository::class => 0,
        ];
        $currentCplId = 1;
        $newCplId = 3;

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => $newCplId]);
        $rule = new CombinedPriceListActivationRule();
        $rule->setCombinedPriceList($cpl);

        $this->configureRepositories($actualityPerRepo, $rule);

        $this->configManager->expects($this->once())
            ->method('set')
            ->with('oro_pricing.combined_price_list', $newCplId);
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturn($currentCplId);
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->triggerHandler->expects($this->once())->method('startCollect');
        $this->triggerHandler->expects($this->once())
            ->method('process');
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->resolver->updateRelations();
    }

    public function testUpdateRelationsSameConfigCpl()
    {
        $actualityPerRepo = [
            CombinedPriceListToCustomerGroupRepository::class => 0,
            CombinedPriceListToCustomerRepository::class => 0,
            CombinedPriceListToWebsiteRepository::class => 0,
        ];
        $currentCplId = 1;
        $newCplId = 1;

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => $newCplId]);
        $rule = new CombinedPriceListActivationRule();
        $rule->setCombinedPriceList($cpl);

        $this->configureRepositories($actualityPerRepo, $rule);

        $this->configManager->expects($this->never())
            ->method('set');
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturn($currentCplId);
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->triggerHandler->expects($this->once())->method('startCollect');
        $this->triggerHandler->expects($this->never())
            ->method('process');
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->resolver->updateRelations();
    }

    public function testUpdateRelationsHasTriggers()
    {
        $actualityPerRepo = [
            CombinedPriceListToCustomerGroupRepository::class => 1,
            CombinedPriceListToCustomerRepository::class => 3,
            CombinedPriceListToWebsiteRepository::class => 4,
        ];
        $currentCplId = 1;
        $newCplId = 3;

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => $newCplId]);
        $rule = new CombinedPriceListActivationRule();
        $rule->setCombinedPriceList($cpl);

        $this->configureRepositories($actualityPerRepo, $rule);

        $this->configManager->expects($this->once())
            ->method('set')
            ->with('oro_pricing.combined_price_list', $newCplId);
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturn($currentCplId);
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->triggerHandler->expects($this->once())->method('startCollect');
        $this->triggerHandler->expects($this->exactly(2))
            ->method('process');
        $this->triggerHandler->expects($this->once())
            ->method('commit');

        $this->resolver->updateRelations();
    }

    private function configureRepositories(array $actualityPerRepo, CombinedPriceListActivationRule $rule): void
    {
        $CPLCustomerGroupRepositoryMock = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $CPLCustomerGroupRepositoryMock->expects($this->once())
            ->method('updateActuality')
            ->willReturn($actualityPerRepo[CombinedPriceListToCustomerGroupRepository::class]);

        $CPLCustomerRepositoryMock = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $CPLCustomerRepositoryMock->expects($this->once())
            ->method('updateActuality')
            ->willReturn($actualityPerRepo[CombinedPriceListToCustomerGroupRepository::class]);

        $CPLWebsiteRepositoryMock = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $CPLWebsiteRepositoryMock->expects($this->once())
            ->method('updateActuality')
            ->willReturn($actualityPerRepo[CombinedPriceListToCustomerGroupRepository::class]);

        $activationRuleRepositoryMock = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $activationRuleRepositoryMock->expects($this->once())
            ->method('getNewActualRules')
            ->willReturn([$rule]);
        $activationRuleRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->willReturn($rule);

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturnMap([
            [CombinedPriceListToCustomer::class, $CPLCustomerRepositoryMock],
            [CombinedPriceListToCustomerGroup::class, $CPLCustomerGroupRepositoryMock],
            [CombinedPriceListToWebsite::class, $CPLWebsiteRepositoryMock],
            [CombinedPriceListActivationRule::class, $activationRuleRepositoryMock],
        ]);

        $this->registry->method('getManagerForClass')
            ->willReturn($manager);
    }
}
