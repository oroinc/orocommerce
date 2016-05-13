<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;

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

    protected function setUp()
    {
        $this->combinedPriceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var  $registry */
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->garbageCollector = new CombinedPriceListGarbageCollector($this->registry, $this->configManager);
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
        $repository->expects($this->once())
            ->method('deleteUnusedPriceLists')
            ->with($expectedParams);

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
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $accountRelationRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $accountGroupRelationRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $websiteRelationRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getRepository')
            ->willReturnMap([
                ['OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList', $repository],
                ['OroB2BPricingBundle:CombinedPriceListToAccount', $accountRelationRepository],
                ['OroB2BPricingBundle:CombinedPriceListToAccountGroup', $accountGroupRelationRepository],
                ['OroB2BPricingBundle:CombinedPriceListToWebsite', $websiteRelationRepository],
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
