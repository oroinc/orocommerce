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
     * @var CombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var CombinedPriceListGarbageCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $garbageCollector;

    /**
     * @var PriceListCollectionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $combinedPriceListProvider;

    /**
     * @var WebsiteCombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteBuilder;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListCollectionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinedPriceListProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->garbageCollector = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteBuilder = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new CombinedPriceListsBuilder(
            $this->configManager,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector
        );
        $this->builder->setWebsiteCombinedPriceListBuilder($this->websiteBuilder);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param int $configCPLId
     * @param int $actualCPLId
     * @param bool $force
     */
    public function testBuild($configCPLId, $actualCPLId, $force)
    {
        $combinedPriceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList');
        $combinedPriceList->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($actualCPLId));
        $priceListsCollection = [
            $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\PriceListSequenceMember')
                ->disableOriginalConstructor()
                ->getMock()
        ];

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByConfig')
            ->will($this->returnValue($priceListsCollection));
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListsCollection, $force)
            ->will($this->returnValue($combinedPriceList));

        $key = implode(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            [OroB2BPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
        );
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($configCPLId);

        if ($actualCPLId !== $configCPLId) {
            $this->assertUpdateCombinedPriceListConnection($actualCPLId, $key);
        } else {
            $this->configManager->expects($this->never())
                ->method('set');
        }

        $this->websiteBuilder->expects($this->once())
            ->method('build')
            ->with(null, $force);

        $this->builder->build($force);
    }

    /**
     * @return array
     */
    public function testBuildDataProvider()
    {
        return [
            'no changes' => [
                'configCPLId' => 1,
                'actualCPLId' => 1,
                false
            ],
            'change cpl' => [
                'configCPLId' => 1,
                'actualCPLId' => 2,
                false
            ],
            'new cpl' => [
                'configCPLId' => null,
                'actualCPLId' => 1,
                false
            ],
            'no changes force' => [
                'configCPLId' => 1,
                'actualCPLId' => 1,
                true
            ],
            'change cpl force' => [
                'configCPLId' => 1,
                'actualCPLId' => 2,
                true
            ],
            'new cpl force' => [
                'configCPLId' => null,
                'actualCPLId' => 1,
                true
            ],
        ];
    }

    /**
     * @param int $actualCPLId
     * @param string $key
     */
    protected function assertUpdateCombinedPriceListConnection($actualCPLId, $key)
    {
        $this->configManager->expects($this->once())
            ->method('set')
            ->with($key, $actualCPLId);
        $this->configManager->expects($this->once())
            ->method('flush');
    }
}
