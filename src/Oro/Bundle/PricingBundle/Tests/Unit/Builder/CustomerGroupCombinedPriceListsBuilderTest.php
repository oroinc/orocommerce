<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CustomerGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyFallbackAwareInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerGroupCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    use EntityTrait;

    /**
     * @var CustomerGroupCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var CustomerCombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerBuilder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->customerBuilder = $this->createMock(CustomerCombinedPriceListsBuilder::class);
        $this->builder = new CustomerGroupCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $this->strategyRegister,
            $this->triggerHandler
        );
        $this->configureBuilderClasses($this->builder);
    }

    /**
     * @param CustomerGroupCombinedPriceListsBuilder $builder
     */
    protected function configureBuilderClasses(CustomerGroupCombinedPriceListsBuilder $builder)
    {
        $builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $builder->setCustomerCombinedPriceListsBuilder($this->customerBuilder);
        $builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $builder->setFallbackClassName($this->fallbackClass);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param PriceListToCustomerGroup $priceListByCustomerGroup
     * @param bool $force
     */
    public function testBuildForAll($priceListByCustomerGroup, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByCustomerGroup);

        $fallback = $force ? null : PriceListCustomerGroupFallback::WEBSITE;

        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getCustomerGroupIteratorByDefaultFallback')
            ->with($website, $fallback)
            ->will($this->returnValue([$customerGroup]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        $this->fallbackRepository->expects($this->exactly($callExpects))->method('findOneBy');
        if (!$priceListByCustomerGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($customerGroup, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($website, $customerGroup, $force);
        }

        $this->builder->build($website, null, $force);
        $this->builder->build($website, null, $force);
    }

    /**
     * @return array
     */
    public function testBuildDataProvider()
    {
        return [
            [
                'priceListByCustomerGroup' => null,
                'force' => true
            ],
            [
                'priceListByCustomerGroup' => null,
                'force' => false
            ],
            [
                'priceListByCustomerGroup' => new PriceListToCustomerGroup(),
                'force' => false
            ],
            [
                'priceListByCustomerGroup' => new PriceListToCustomerGroup(),
                'force' => true
            ]
        ];
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param PriceListToCustomerGroup $priceListByCustomerGroup
     * @param bool $force
     */
    public function testBuildForCustomerGroup($priceListByCustomerGroup, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByCustomerGroup);
        $this->priceListToEntityRepository->expects($this->never())
            ->method('getCustomerGroupIteratorByDefaultFallback');
        $this->garbageCollector->expects($this->exactly($callExpects))
            ->method('cleanCombinedPriceLists');

        if (!$priceListByCustomerGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($customerGroup, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($website, $customerGroup, $force);
        }

        $this->builder->build($website, $customerGroup, $force);
        $this->builder->build($website, $customerGroup, $force);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsageWithoutFallback()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $forceTimestamp = null;
        $fallback = $this->getEntity(PriceListCustomerGroupFallback::class);

        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);
        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $priceListCollection = [$pl1, $pl2];

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->willReturn($combinedPriceList);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->never())
            ->method('getPriceListsByWebsite');
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containMergeDisallowed');
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containScheduled');

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customerGroup)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customerGroup, $forceTimestamp);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsageNoFallbackPriceLists()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $forceTimestamp = null;
        $fallback = null;

        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);
        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $priceListCollection = [$pl1, $pl2];
        $fallbackCollection = null;

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->willReturn($combinedPriceList);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containMergeDisallowed');
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containScheduled');

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customerGroup)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customerGroup, $forceTimestamp);
    }

    /**
     * @return array
     */
    public function fallbackCplChecksDataProvider()
    {
        return [
            'merge:0,scheduled:1,calculated:1' => [false, true, true],
            'merge:1,scheduled:0,calculated:1' => [true, false, true],
            'merge:1,scheduled:1,calculated:1' => [true, true, true],
            'merge:1,scheduled:1,calculated:0' => [true, true, false],
            'merge:1,scheduled:0,calculated:0' => [true, false, false],
            'merge:0,scheduled:1,calculated:0' => [false, true, false],
            'merge:0,scheduled:0,calculated:0' => [false, false, false],
        ];
    }

    /**
     * @dataProvider fallbackCplChecksDataProvider
     * @param bool $containMergeDisallowed
     * @param bool $containScheduled
     * @param bool $isFallbackCplCalculated
     */
    public function testRebuildForCustomerGroupWithFallbackCplUsageNotAllowed(
        $containMergeDisallowed,
        $containScheduled,
        $isFallbackCplCalculated
    ) {
        $customerGroup = new CustomerGroup();
        $website = new Website();
        $forceTimestamp = null;
        $fallback = null;

        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);
        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $pl3 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 3]), true);
        $priceListCollection = [$pl1, $pl2, $pl3];
        $fallbackCollection = [$pl3];

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $fallbackLevelCpl = $this->getEntity(
            CombinedPriceList::class,
            ['id' => 3, 'pricesCalculated' => $isFallbackCplCalculated]
        );
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceList')
            ->withConsecutive(
                [$priceListCollection],
                [$fallbackCollection]
            )
            ->willReturnOnConsecutiveCalls($combinedPriceList, $fallbackLevelCpl);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containMergeDisallowed')
            ->with($priceListCollection)
            ->willReturn($containMergeDisallowed);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containScheduled')
            ->with($priceListCollection)
            ->willReturn($containScheduled);

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customerGroup)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customerGroup, $forceTimestamp);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsageUnsupportedStrategy()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $forceTimestamp = null;
        $fallback = null;

        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);
        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $pl3 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 3]), true);
        $pl4 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 4]), true);
        $priceListCollection = [$pl1, $pl2, $pl3, $pl4];
        $fallbackCollection = [$pl3, $pl4];

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $fallbackLevelCpl = $this->getEntity(
            CombinedPriceList::class,
            ['id' => 2, 'pricesCalculated' => true]
        );
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceList')
            ->withConsecutive(
                [$priceListCollection],
                [$fallbackCollection]
            )
            ->willReturnOnConsecutiveCalls($combinedPriceList, $fallbackLevelCpl);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containMergeDisallowed')
            ->with($priceListCollection)
            ->willReturn(false);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containScheduled')
            ->with($priceListCollection)
            ->willReturn(false);

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customerGroup)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customerGroup, $forceTimestamp);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsage()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $forceTimestamp = null;
        $fallback = null;

        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);
        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $pl3 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 3]), true);
        $pl4 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 4]), true);
        $pl5 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 5]), true);
        $priceListCollection = [$pl1, $pl2, $pl3, $pl4, $pl5];
        $fallbackCollection = [$pl3, $pl4, $pl5];

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $fallbackLevelCpl = $this->getEntity(
            CombinedPriceList::class,
            ['id' => 2, 'pricesCalculated' => true]
        );
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceList')
            ->withConsecutive(
                [$priceListCollection],
                [$fallbackCollection]
            )
            ->willReturnOnConsecutiveCalls($combinedPriceList, $fallbackLevelCpl);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containScheduled')
            ->with($priceListCollection)
            ->willReturn(false);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containMergeDisallowed')
            ->with($priceListCollection)
            ->willReturn(false);

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customerGroup)
            ->willReturn($relation);

        $combiningStrategy = $this->createMock(PriceCombiningStrategyFallbackAwareInterface::class);
        $strategyRegister = $this->createMock(StrategyRegister::class);
        $strategyRegister->expects($this->any())
            ->method('getCurrentStrategy')
            ->willReturn($combiningStrategy);

        $combiningStrategy->expects($this->once())
            ->method('combinePricesUsingPrecalculatedFallback')
            ->with(
                $combinedPriceList,
                [$pl1, $pl2],
                $fallbackLevelCpl,
                $forceTimestamp
            );

        $builder = new CustomerGroupCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $strategyRegister,
            $this->triggerHandler
        );
        $this->configureBuilderClasses($builder);

        $builder->build($website, $customerGroup, $forceTimestamp);
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @param bool $force
     */
    protected function assertRebuild(Website $website, CustomerGroup $customerGroup, $force)
    {
        $callExpects = 1;
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->will($this->returnValue($combinedPriceList));

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setWebsite($website);
        $relation->setCustomerGroup($customerGroup);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customerGroup)
            ->willReturn($relation);

        $this->customerBuilder->expects($this->exactly($callExpects))
            ->method('buildByCustomerGroup')
            ->with($website, $customerGroup, $force);
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     */
    protected function configurePriceListToEntityRepositoryMock(CustomerGroup $customerGroup, Website $website)
    {
        $this->priceListToEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ]
            )
            ->willReturn(new PriceListToCustomerGroup());
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @param PriceListCustomerGroupFallback|null $fallback
     */
    protected function configureFallbackRepositoryMock(
        CustomerGroup $customerGroup,
        Website $website,
        PriceListCustomerGroupFallback $fallback = null
    ) {
        $this->fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website,
                    'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
                ]
            )
            ->willReturn($fallback);
    }
}
