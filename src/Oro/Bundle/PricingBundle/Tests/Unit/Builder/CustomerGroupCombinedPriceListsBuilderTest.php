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
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyFallbackAwareInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerGroupCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    use EntityTrait;

    /**
     * @var CustomerGroupCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var CustomerCombinedPriceListsBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerBuilder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return PriceListToCustomerGroupRepository::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListFallbackRepositoryClass()
    {
        return PriceListCustomerGroupFallbackRepository::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerBuilder = $this->createMock(CustomerCombinedPriceListsBuilder::class);
        $this->builder = new CustomerGroupCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->cplScheduleResolver,
            $this->strategyRegister,
            $this->triggerHandler
        );
        $this->configureBuilderClasses($this->builder);
    }

    protected function configureBuilderClasses(CustomerGroupCombinedPriceListsBuilder $builder)
    {
        $builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $builder->setCustomerCombinedPriceListsBuilder($this->customerBuilder);
        $builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $builder->setFallbackClassName($this->fallbackClass);
    }

    public function testBuildWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test exception');

        $website = new Website();
        $customerGroup = new CustomerGroup();
        $this->priceListToEntityRepository
            ->expects($this->once())
            ->method('hasAssignedPriceLists')
            ->willReturn(true);

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('beginTransaction');

        $this->combinedPriceListEm
            ->expects($this->never())
            ->method('commit');

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('rollback');

        $this->priceListToEntityRepository
            ->expects($this->once())
            ->method('getCustomerGroupIteratorWithDefaultFallback')
            ->willReturn([$customerGroup]);

        $this->fallbackRepository
            ->expects($this->once())
            ->method('hasFallbackOnNextLevel');

        $this->combinedPriceListToEntityRepository
            ->expects($this->never())
            ->method('delete');

        $this->priceListCollectionProvider
            ->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->will(
                $this->throwException(new \Exception('test exception'))
            );

        $this->builder->build($website, null);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param PriceListToCustomerGroup $priceListByCustomerGroup
     * @param bool                     $force
     */
    public function testBuildForAll($priceListByCustomerGroup, $force = false)
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();

        $this->priceListToEntityRepository
            ->expects($this->once())
            ->method('getCustomerGroupIteratorWithDefaultFallback')
            ->with($website)
            ->willReturn([$customerGroup]);

        $this->assertBuilderCallsWithoutFallback($priceListByCustomerGroup, $force, $website, $customerGroup);

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
        $website = new Website();
        $customerGroup = new CustomerGroup();

        $this->priceListToEntityRepository->expects($this->never())
            ->method('getCustomerGroupIteratorWithDefaultFallback');

        $this->assertBuilderCallsWithoutFallback($priceListByCustomerGroup, $force, $website, $customerGroup);

        $this->builder->build($website, $customerGroup, $force);
        $this->builder->build($website, $customerGroup, $force);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsageWithoutFallback()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $forceTimestamp = null;
        /** @var PriceListCustomerGroupFallback $fallback */
        $fallback = false;

        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);
        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $priceListCollection = [$pl1, $pl2];

        $this->configureTransactionWrappingForOneCall();

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
        $fallback = true;

        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);
        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $priceListCollection = [$pl1, $pl2];
        $fallbackCollection = null;

        $this->configureTransactionWrappingForOneCall();

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
        $fallback = true;

        $this->configureFallbackRepositoryMock($customerGroup, $website, $fallback);
        $this->configurePriceListToEntityRepositoryMock($customerGroup, $website);

        $this->configureTransactionWrappingForOneCall();

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
        $fallback = true;

        $this->configureTransactionWrappingForOneCall();

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
        $fallback = true;

        $this->configureTransactionWrappingForOneCall();

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

    protected function configurePriceListToEntityRepositoryMock(CustomerGroup $customerGroup, Website $website)
    {
        $this->priceListToEntityRepository->expects($this->any())
            ->method('hasAssignedPriceLists')
            ->with($website, $customerGroup)
            ->willReturn(true);
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @param bool $hasFallback
     */
    protected function configureFallbackRepositoryMock(
        CustomerGroup $customerGroup,
        Website $website,
        $hasFallback = true
    ) {
        $this->fallbackRepository->expects($this->once())
            ->method('hasFallbackOnNextLevel')
            ->with($website, $customerGroup)
            ->willReturn($hasFallback);
    }

    protected function assertBuilderCallsWithoutFallback(
        $priceListByCustomerGroup,
        $force,
        Website $website,
        CustomerGroup $customerGroup
    ): void {
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('hasAssignedPriceLists')
            ->willReturn($priceListByCustomerGroup !== null);

        $this->configureTransactionWrappingForOneCall();

        $this->fallbackRepository->expects($this->once())
            ->method('hasFallbackOnNextLevel')
            ->willReturn(false);

        $expectation = !$priceListByCustomerGroup ? $this->once() : $this->never();
        $this->combinedPriceListToEntityRepository
            ->expects($expectation)
            ->method('delete')
            ->with($customerGroup, $website);

        $this->assertRebuild($website, $customerGroup, $force);
    }
}
