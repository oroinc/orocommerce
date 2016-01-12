<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;

class CombinedPriceListsBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testBuildDataProvider
     * @param $configCPLId
     * @param $priceListCollection
     * @param $actualCPLId
     */
    public function testBuild($configCPLId, $priceListCollection, $actualCPLId)
    {
        $websiteCPLBuilder = $this->getWebsiteCPLBuilder();
        $configManager = $this->getConfigManagerMock($configCPLId);
        if ($configCPLId != $actualCPLId) {
            $configManager->expects($this->once())->method('set');
        } else {
            $configManager->expects($this->never())->method('set');
        }
        $priceListCollectionProvider = $this->getPriceListCollectionProviderMock($priceListCollection);
        $CPLProvider = $this->getCombinedPriceListProviderMock($priceListCollection, $actualCPLId);
        $garbageCollector = $this->getGarbageCollector();

        $CPLBuilder = new CombinedPriceListsBuilder();
        $CPLBuilder->setWebsiteCombinedPriceListBuilder($websiteCPLBuilder);
        $CPLBuilder->setConfigManager($configManager);
        $CPLBuilder->setPriceListCollectionProvider($priceListCollectionProvider);
        $CPLBuilder->setCombinedPriceListProvider($CPLProvider);
        $CPLBuilder->setCombinedPriceListGarbageCollector($garbageCollector);

        $CPLBuilder->build();
    }

    /**
     * @return array
     */
    public function testBuildDataProvider()
    {
        return [
            'no changes' => [
                'configCPLId' => 1,
                'priceListCollection' => [1,2,3],
                'actualCPLId' => 1,
            ],
            'change cpl' => [
                'configCPLId' => 1,
                'priceListCollection' => [1,2,3],
                'actualCPLId' => 2,
            ],
            'new cpl' => [
                'configCPLId' => null,
                'priceListCollection' => [1,2,3],
                'actualCPLId' => 1,
            ],
        ];
    }

    /**
     * @param $configCPLId
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected function getConfigManagerMock($configCPLId)
    {
        $key = implode(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            [OroB2BPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
        );
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($configCPLId);

        return $configManager;
    }

    /**
     * @param $priceListCollection
     * @return \PHPUnit_Framework_MockObject_MockObject|PriceListCollectionProvider
     */
    protected function getPriceListCollectionProviderMock($priceListCollection)
    {
        $providerClass = 'OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider';
        $priceListCollectionProvider = $this->getMockBuilder($providerClass)
            ->disableOriginalConstructor()
            ->getMock();

        $priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByConfig')
            ->willReturn($priceListCollection);

        return $priceListCollectionProvider;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WebsiteCombinedPriceListsBuilder
     */
    protected function getWebsiteCPLBuilder()
    {
        $websiteCPLBuilderClass = 'OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder';
        $websiteCPLBuilder = $this->getMockBuilder($websiteCPLBuilderClass)
            ->disableOriginalConstructor()
            ->getMock();
        $websiteCPLBuilder->expects($this->once())->method('buildForAll');

        return $websiteCPLBuilder;
    }

    /**
     * @param $collection
     * @param $actualCPLId
     * @return CombinedPriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListProviderMock($collection, $actualCPLId)
    {
        $actualCPL = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList')
            ->disableOriginalConstructor()
            ->getMock();

        $actualCPL->expects($this->any())->method('getId')->willReturn($actualCPLId);

        $CPLProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $CPLProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($collection)
            ->willReturn($actualCPL);

        return $CPLProvider;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CombinedPriceListGarbageCollector
     */
    protected function getGarbageCollector()
    {
        $collector = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $collector->expects($this->once())->method('cleanCombinedPriceLists');

        return $collector;
    }
}
