<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

class CombinedPriceListScheduleResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    public function setUp()
    {
        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountGroupRepository';
        $CPLAccountGroupRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $CPLAccountGroupRepositoryMock->expects($this->once())->method('updateActuality');

        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountRepository';
        $CPLAccountRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $CPLAccountRepositoryMock->expects($this->once())->method('updateActuality');

        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository';
        $CPLWebsiteRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $CPLWebsiteRepositoryMock->expects($this->once())->method('updateActuality');

        $className = 'OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository';
        $activationRuleRepositoryMock = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
        $activationRuleRepositoryMock->expects($this->any())
            ->method('getNewActualRules')
            ->willReturn([new CombinedPriceListActivationRule()]);

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->manager->method('getRepository')->willReturnMap([
            ['OroB2BPricingBundle:CombinedPriceListToAccount', $CPLAccountRepositoryMock],
            ['OroB2BPricingBundle:CombinedPriceListToAccountGroup', $CPLAccountGroupRepositoryMock],
            ['OroB2BPricingBundle:CombinedPriceListToWebsite', $CPLWebsiteRepositoryMock],
            ['OroB2BPricingBundle:CombinedPriceListActivationRule', $activationRuleRepositoryMock],
        ]);
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->method('getManagerForClass')->willReturn($this->manager);
    }

    public function testUpdateRelations()
    {
        $resolver = new CombinedPriceListScheduleResolver($this->registry);
        $resolver->addRelationClass('OroB2BPricingBundle:CombinedPriceListToAccount');
        $resolver->addRelationClass('OroB2BPricingBundle:CombinedPriceListToAccountGroup');
        $resolver->addRelationClass('OroB2BPricingBundle:CombinedPriceListToWebsite');
        $resolver->updateRelations();
    }
}
