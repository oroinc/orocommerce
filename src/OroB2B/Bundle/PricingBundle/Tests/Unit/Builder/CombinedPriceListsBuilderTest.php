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
     * @param int $behavior
     * @param bool $force
     */
    public function testBuild($configCPLId, $actualCPLId, $behavior, $force = false)
    {
        $callExpects = 1;
        $combinedPriceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList');
        $combinedPriceList->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($actualCPLId));
        $priceListsCollection = [
            $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\PriceListSequenceMember')
                ->disableOriginalConstructor()
                ->getMock()
        ];

        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByConfig')
            ->will($this->returnValue($priceListsCollection));
        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListsCollection, $behavior)
            ->will($this->returnValue($combinedPriceList));

        $key = implode(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            [OroB2BPricingExtension::ALIAS, Configuration::COMBINED_PRICE_LIST]
        );
        $this->configManager->expects($this->exactly($callExpects))
            ->method('get')
            ->with($key)
            ->willReturn($configCPLId);

        if ($actualCPLId !== $configCPLId) {
            $this->assertUpdateCombinedPriceListConnection($actualCPLId, $key);
        } else {
            $this->configManager->expects($this->never())
                ->method('set');
        }

        $this->websiteBuilder->expects($this->exactly($callExpects))
            ->method('build')
            ->with(null, $behavior, $force);

        $this->builder->build($force);
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
                'force' => true
            ],
            'change cpl' => [
                'configCPLId' => 1,
                'actualCPLId' => 2,
            ],
            'new cpl' => [
                'configCPLId' => null,
                'actualCPLId' => 1,
                'force' => true
            ],
            'no changes force' => [
                'configCPLId' => 1,
                'actualCPLId' => 1,
            ],
            'change cpl force' => [
                'configCPLId' => 1,
                'actualCPLId' => 2,
            ],
            'new cpl force' => [
                'configCPLId' => null,
                'actualCPLId' => 1,
                'force' => true
            ],
        ];
    }

    /**
     * @param int $actualCPLId
     * @param string $key
     */
    protected function assertUpdateCombinedPriceListConnection($actualCPLId, $key)
    {
        $callExpects = 1;
        $this->configManager->expects($this->exactly($callExpects))
            ->method('set')
            ->with($key, $actualCPLId);
        $this->configManager->expects($this->exactly($callExpects))
            ->method('flush');
    }
}
