<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CombinedPriceListsBuilderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var string
     */
    protected $combinedPriceListClassName = 'CombinedPriceListClassName';

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var PriceListCollectionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $combinedPriceListProvider;

    /**
     * @var WebsiteCombinedPriceListsBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteBuilder;

    /**
     * @var CombinedPriceListScheduleResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cplScheduleResolver;

    /**
     * @var MergePricesCombiningStrategy|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceResolver;

    /**
     * @var CombinedPriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $triggerHandler;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->registry = $this->createMock(Registry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->combinedPriceListClassName)
            ->willReturn($this->em);

        $this->priceListCollectionProvider = $this->createMock(PriceListCollectionProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->websiteBuilder = $this->createMock(WebsiteCombinedPriceListsBuilder::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);

        $this->cplScheduleResolver = $this->createMock(CombinedPriceListScheduleResolver::class);
        $priceResolver = $this->createMock(MergePricesCombiningStrategy::class);
        $strategyRegister = new StrategyRegister($this->configManager);
        $strategyRegister->add(MergePricesCombiningStrategy::NAME, $priceResolver);

        $this->builder = new CombinedPriceListsBuilder(
            $this->registry,
            $this->configManager,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->cplScheduleResolver,
            $strategyRegister,
            $this->triggerHandler,
            $this->websiteBuilder
        );

        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClassName);
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
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => $actualCPLId]);
        $priceListsCollection = [
            $this->createMock(PriceListSequenceMember::class)
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

        $this->em->expects($this->exactly($callExpects))
            ->method('beginTransaction');
        $this->em->expects($this->exactly($callExpects))
            ->method('commit');
        $this->triggerHandler
            ->expects($this->never())
            ->method('rollback');

        $this->triggerHandler->expects($this->exactly($callExpects))
            ->method('startCollect');
        $this->triggerHandler->expects($this->exactly($callExpects))
            ->method('commit');
        $this->triggerHandler->expects($this->never())
            ->method('rollback');

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

    public function testBuildWithExceptionWhileUpdatePriceLists()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Exception while update price list');

        $priceListsCollection = [
            $this->createMock(PriceListSequenceMember::class)
        ];

        $this->triggerHandler
            ->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler
            ->expects($this->once())
            ->method('rollback');
        $this->triggerHandler
            ->expects($this->never())
            ->method('commit');

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');
        $this->em
            ->expects($this->once())
            ->method('rollback');
        $this->em
            ->expects($this->never())
            ->method('commit');

        $this->priceListCollectionProvider
            ->expects($this->once())
            ->method('getPriceListsByConfig')
            ->will($this->returnValue($priceListsCollection));

        $this->combinedPriceListProvider
            ->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListsCollection)
            ->will(
                $this->throwException(new \Exception('Exception while update price list'))
            );

        $this->builder->build();
    }

    public function testBuildWithExceptionWhileDoNestedBuild()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Exception while build website combined pl');

        $configCPLId = 1;
        $actualCPLId = 1;
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => $configCPLId]);
        $priceListsCollection = [
            $this->createMock(PriceListSequenceMember::class)
        ];

        $this->triggerHandler
            ->expects($this->once())
            ->method('startCollect');
        $this->triggerHandler
            ->expects($this->once())
            ->method('rollback');
        $this->triggerHandler
            ->expects($this->never())
            ->method('commit');

        $this->em
            ->expects($this->once())
            ->method('beginTransaction');
        $this->em
            ->expects($this->once())
            ->method('commit');
        $this->em
            ->expects($this->never())
            ->method('rollback');

        $this->priceListCollectionProvider
            ->expects($this->once())
            ->method('getPriceListsByConfig')
            ->will($this->returnValue($priceListsCollection));

        $this->combinedPriceListProvider
            ->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListsCollection)
            ->willReturn($combinedPriceList);

        $fullKey = Configuration::getConfigKeyToFullPriceList();
        $key = Configuration::getConfigKeyToPriceList();
        //1 from register + 2 from strategy
        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [$fullKey, false, false, null, $configCPLId],
                [$key, false, false, null, $actualCPLId],
                ['oro_pricing.price_strategy', false, false, null, MergePricesCombiningStrategy::NAME],
            ]);

        $this->configManager
            ->expects($this->never())
            ->method('set');

        $this->websiteBuilder
            ->expects($this->once())
            ->method('build')
            ->will(
                $this->throwException(new \Exception('Exception while build website combined pl'))
            );

        $this->builder->build();
    }
}
