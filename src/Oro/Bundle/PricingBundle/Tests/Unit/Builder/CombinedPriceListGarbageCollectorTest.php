<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;

class CombinedPriceListGarbageCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $combinedPriceListClass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $garbageCollector;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    protected function setUp()
    {
        $this->combinedPriceListClass = 'Oro\Bundle\PricingBundle\Entity\CombinedPriceList';
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var  $registry */
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggerHandler = $this->getMockBuilder(CombinedPriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->garbageCollector = new CombinedPriceListGarbageCollector(
            $this->registry,
            $this->configManager,
            $this->triggerHandler
        );
        $this->garbageCollector->setCombinedPriceListClass($this->combinedPriceListClass);
    }

    /**
     * @dataProvider getCleanCombinedPriceListsProvider
     * @param int $configCombinedPriceListId
     * @param array $expectedParams
     */
    public function testCleanCombinedPriceLists($configCombinedPriceListId, $expectedParams)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn($configCombinedPriceListId);

        $repository = $this->assertRepositoryCall();
        $invalidCPLs = [1];
        $repository->expects($this->once())
            ->method('getUnusedPriceListsIds')
            ->with($expectedParams)
            ->willReturn($invalidCPLs);
        $repository->expects($this->once())
            ->method('deletePriceLists')
            ->with($invalidCPLs);

        $this->triggerHandler->expects($this->once())->method('startCollect');
        $this->triggerHandler->expects($this->once())->method('massProcess')->with($invalidCPLs);
        $this->triggerHandler->expects($this->once())->method('commit');

        $this->garbageCollector->cleanCombinedPriceLists();
    }

    /**
     * @return array
     */
    public function getCleanCombinedPriceListsProvider()
    {
        return [
            'testWithoutCPL' => [
                'configCombinedPriceListId' => null,
                'expectedParams' => [],
            ],
            'testWithCPL' => [
                'configCombinedPriceListId' => 1,
                'expectedParams' => [1],
            ],
        ];
    }

    /**
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertRepositoryCall()
    {
        $repository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $accountRelationRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $accountGroupRelationRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $websiteRelationRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getRepository')
            ->willReturnMap([
                ['Oro\Bundle\PricingBundle\Entity\CombinedPriceList', $repository],
                ['OroPricingBundle:CombinedPriceListToAccount', $accountRelationRepository],
                ['OroPricingBundle:CombinedPriceListToAccountGroup', $accountGroupRelationRepository],
                ['OroPricingBundle:CombinedPriceListToWebsite', $websiteRelationRepository],
            ]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->combinedPriceListClass)
            ->willReturn($em);
        $this->registry->expects($this->once())
            ->method('getManager')
            ->willReturn($em);

        return $repository;
    }
}
