<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

class CombinedPriceListScheduleResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    public function setUp()
    {
        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository';
        $CPLCustomerGroupRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $CPLCustomerGroupRepositoryMock->expects($this->once())->method('updateActuality');

        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository';
        $CPLCustomerRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $CPLCustomerRepositoryMock->expects($this->once())->method('updateActuality');

        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository';
        $CPLWebsiteRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $CPLWebsiteRepositoryMock->expects($this->once())->method('updateActuality');

        $className = 'Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository';
        $activationRuleRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $rule = new CombinedPriceListActivationRule();
        $rule->setCombinedPriceList(new CombinedPriceList());
        $activationRuleRepositoryMock->expects($this->once())
            ->method('getNewActualRules')
            ->willReturn([$rule]);
        $activationRuleRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->willReturn($rule);
        $this->manager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroPricingBundle:CombinedPriceListToCustomer', $CPLCustomerRepositoryMock],
            ['OroPricingBundle:CombinedPriceListToCustomerGroup', $CPLCustomerGroupRepositoryMock],
            ['OroPricingBundle:CombinedPriceListToWebsite', $CPLWebsiteRepositoryMock],
            ['OroPricingBundle:CombinedPriceListActivationRule', $activationRuleRepositoryMock],
        ]);
        $this->registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->method('getManagerForClass')->willReturn($this->manager);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())->method('set');
        $this->configManager->expects($this->any())->method('get')->willReturn(1);
        $this->configManager->expects($this->once())->method('flush');

        $this->triggerHandler = $this->getMockBuilder(CombinedPriceListTriggerHandler::class)
            ->disableOriginalConstructor()->getMock();

        $this->triggerHandler->expects($this->once())->method('startCollect');
        //once because new rule + for config
        $this->triggerHandler->expects($this->exactly(2))->method('process');
        $this->triggerHandler->expects($this->once())->method('commit');
    }

    public function testUpdateRelations()
    {
        $resolver = new CombinedPriceListScheduleResolver($this->registry, $this->configManager, $this->triggerHandler);
        $resolver->addRelationClass('OroPricingBundle:CombinedPriceListToCustomer');
        $resolver->addRelationClass('OroPricingBundle:CombinedPriceListToCustomerGroup');
        $resolver->addRelationClass('OroPricingBundle:CombinedPriceListToWebsite');
        $resolver->updateRelations();
    }
}
