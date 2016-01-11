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

    protected function setUp()
    {
        $this->combinedPriceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';
    }

    /**
     * @dataProvider getCleanCombinedPriceListsProvider
     * @param $configCombinedPriceListId
     * @param $expectedParams
     */
    public function testCleanCombinedPriceLists($configCombinedPriceListId, $expectedParams)
    {
        /**
         * @var $configManager \PHPUnit_Framework_MockObject_MockObject|ConfigManager
         */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->once())
            ->method('get')
            ->willReturn($configCombinedPriceListId);

        $CPLGarbageCollector = new CombinedPriceListGarbageCollector($this->getRegistryWithRepository($expectedParams));
        $CPLGarbageCollector->setCombinedPriceListClass($this->combinedPriceListClass);
        $CPLGarbageCollector->setConfigManager($configManager);
        $CPLGarbageCollector->cleanCombinedPriceLists();
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
     * @param array $expectedParams
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistryWithRepository(array $expectedParams)
    {
        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('deleteUnusedPriceLists')
            ->with($expectedParams);
        /**
         * @var $registry \PHPUnit_Framework_MockObject_MockObject|Registry
         */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->combinedPriceListClass)
            ->willReturn($em);

        return $registry;
    }
}
