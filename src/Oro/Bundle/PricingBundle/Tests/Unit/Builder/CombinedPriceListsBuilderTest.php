<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;

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

    /**
     * @var CombinedPriceListScheduleResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cplScheduleResolver;

    /**
     * @var CombinedProductPriceResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceResolver;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListCollectionProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinedPriceListProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->garbageCollector = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteBuilder = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $className = 'Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver';
        $this->cplScheduleResolver = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $className = 'Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver';
        $this->priceResolver = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new CombinedPriceListsBuilder(
            $this->configManager,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $this->priceResolver
        );
        $this->builder->setWebsiteCombinedPriceListBuilder($this->websiteBuilder);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param int $configCPLId
     * @param int $actualCPLId
     * @param bool $force
     */
    public function testBuild($configCPLId, $actualCPLId, $force = false)
    {
        $callExpects = 1;
        $combinedPriceList = $this->getMock('Oro\Bundle\PricingBundle\Entity\CombinedPriceList');
        $combinedPriceList->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($actualCPLId));
        $priceListsCollection = [
            $this->getMockBuilder('Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember')
                ->disableOriginalConstructor()
                ->getMock()
        ];

        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByConfig')
            ->will($this->returnValue($priceListsCollection));
        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListsCollection)
            ->will($this->returnValue($combinedPriceList));

        $fullKey = Configuration::getConfigKeyToFullPriceList();
        $key = Configuration::getConfigKeyToPriceList();
        $this->configManager->expects($this->exactly($callExpects * 2))
            ->method('get')
            ->willReturnMap([
                [$fullKey, $configCPLId],
                [$key, $actualCPLId],
            ]);

        if ($actualCPLId !== $configCPLId) {
            $this->configManager->expects($this->any())
                ->method('set');
            $this->configManager->expects($this->any())
                ->method('flush');
        } else {
            $this->configManager->expects($this->exactly(2))
                ->method('set');
        }

        $this->websiteBuilder->expects($this->exactly($callExpects))
            ->method('build')
            ->with(null, $force);

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
                'force' => false
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
                'force' => false
            ],
            'new cpl force' => [
                'configCPLId' => null,
                'actualCPLId' => 1,
                'force' => true
            ],
        ];
    }
}
