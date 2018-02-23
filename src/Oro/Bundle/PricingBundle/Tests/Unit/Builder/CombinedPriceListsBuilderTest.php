<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;

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
     * @var MergePricesCombiningStrategy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceResolver;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerHandler;

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
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $className = 'Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver';
        $this->cplScheduleResolver = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
        $priceResolver = self::createMock(MergePricesCombiningStrategy::class);
        $strategyRegister = new StrategyRegister($this->configManager);
        $strategyRegister->add(MergePricesCombiningStrategy::NAME, $priceResolver);

        $this->builder = new CombinedPriceListsBuilder(
            $this->configManager,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $strategyRegister
        );
        $this->builder->setWebsiteCombinedPriceListBuilder($this->websiteBuilder);
        $this->builder->setCombinedPriceListTriggerHandler($this->triggerHandler);
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
        $combinedPriceList = $this->createMock('Oro\Bundle\PricingBundle\Entity\CombinedPriceList');
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
        //1 from register + 2 from strategy
        $this->configManager->expects($this->exactly($callExpects * 2 + 1))
            ->method('get')
            ->willReturnMap([
                [$fullKey, false, false, null, $configCPLId],
                [$key, false, false, null, $actualCPLId],
                ['oro_pricing.price_strategy', false, false, null, MergePricesCombiningStrategy::NAME],
            ]);

        if ($actualCPLId !== $configCPLId) {
            $this->configManager->expects($this->any())
                ->method('set');
            $this->configManager->expects($this->any())
                ->method('flush');
        } else {
            $this->configManager->expects($this->never())
                ->method('set');
        }

        $this->websiteBuilder->expects($this->exactly($callExpects))
            ->method('build')
            ->with(null, $force);

        $this->triggerHandler->expects($this->exactly($callExpects))
            ->method('startCollect');
        $this->triggerHandler->expects($this->exactly($callExpects))
            ->method('commit');

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
