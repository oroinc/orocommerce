<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyFallbackAwareInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    use EntityTrait;

    /**
     * @var CustomerCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return PriceListToCustomerRepository::class;
    }

    /**
     * @return string
     */
    protected function getPriceListFallbackRepositoryClass()
    {
        return PriceListCustomerFallbackRepository::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new CustomerCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->cplScheduleResolver,
            $this->strategyRegister,
            $this->triggerHandler
        );
        $this->configureBuilderClasses($this->builder);
    }

    protected function configureBuilderClasses(CustomerCombinedPriceListsBuilder $builder)
    {
        $builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $builder->setFallbackClassName($this->fallbackClass);
    }

    public function testBuildWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test exception');

        $website = new Website();
        $customer = new Customer();
        $this->priceListToEntityRepository
            ->expects($this->once())
            ->method('hasAssignedPriceLists')
            ->willReturn(false);

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('beginTransaction');

        $this->combinedPriceListEm
            ->expects($this->never())
            ->method('commit');

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('rollback');

        $this->fallbackRepository
            ->expects($this->once())
            ->method('hasFallbackOnNextLevel')
            ->willReturn(false);

        $this->combinedPriceListToEntityRepository
            ->expects($this->once())
            ->method('delete');

        $this->priceListCollectionProvider
            ->expects($this->once())
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->will(
                $this->throwException(new \Exception('test exception'))
            );

        $this->builder->build($website, $customer);
    }

    /**
     * @dataProvider buildDataProvider
     * @param PriceListToCustomer $priceListByCustomer
     * @param int|null $force
     */
    public function testBuild($priceListByCustomer, $force)
    {
        $website = new Website();
        $customer = new Customer();

        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('hasAssignedPriceLists')
            ->willReturn($priceListByCustomer !== null);

        $this->configureTransactionWrappingForOneCall();

        $this->fallbackRepository->expects($this->once())
            ->method('hasFallbackOnNextLevel')
            ->willReturn(false);
        $expectation = !$priceListByCustomer ? $this->once() : $this->never();

        $this->combinedPriceListToEntityRepository
            ->expects($expectation)
            ->method('delete')
            ->with($customer, $website);

        $this->assertRebuild($website, $customer);

        $this->builder->build($website, $customer, $force);
        $this->builder->build($website, $customer, $force);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'priceListByCustomer' => null,
                'force' => null
            ],
            [
                'priceListByCustomer' => null,
                'force' => time()
            ],
            [
                'priceListByCustomer' => new PriceListToCustomer(),
                'force' => null
            ],
            [
                'priceListByCustomer' => new PriceListToCustomer(),
                'force' => time()
            ],
        ];
    }

    /**
     * @dataProvider buildDataProviderByCustomerGroup
     * @param PriceListToCustomer $priceListByCustomer
     * @param bool $force
     */
    public function testBuildByCustomerGroup($priceListByCustomer, $force = false)
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 3]);
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('getCustomersWithAssignedPriceLists')
            ->with($website, $customerGroup)
            ->willReturn([3 => ($priceListByCustomer !== null)]);

        $this->configureTransactionWrappingForOneCall();

        $this->priceListToEntityRepository->expects($this->once())
            ->method('getCustomerIteratorWithDefaultFallback')
            ->with($customerGroup, $website)
            ->willReturn([$customer]);

        if ($priceListByCustomer) {
            $this->combinedPriceListToEntityRepository->expects($this->never())
                ->method('delete');

            $this->assertRebuild($website, $customer);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->once())
                ->method('delete')
                ->with($customer, $website);

            $this->priceListCollectionProvider->expects($this->never())
                ->method('getPriceListsByCustomer');
            $this->combinedPriceListRepository->expects($this->never())
                ->method('updateCombinedPriceListConnection');
        }

        $this->builder->buildByCustomerGroup($website, $customerGroup, $force);
        $this->builder->buildByCustomerGroup($website, $customerGroup, $force);
    }

    /**
     * @dataProvider buildDataProviderByCustomerGroup
     * @param PriceListToCustomer $priceListByCustomer
     * @param bool $force
     */
    public function testBuildForCustomersWithoutGroupAndFallbackToGroup($priceListByCustomer, $force = false)
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 3]);
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('getCustomersWithAssignedPriceLists')
            ->with($website)
            ->willReturn([3 => ($priceListByCustomer !== null)]);

        $this->configureTransactionWrappingForOneCall();

        $this->priceListToEntityRepository->expects($this->exactly(2))
            ->method('getAllCustomersWithEmptyGroupAndDefaultFallback')
            ->with($website)
            ->willReturn([$customer]);

        if ($priceListByCustomer) {
            $this->combinedPriceListToEntityRepository->expects($this->never())
                ->method('delete');

            $this->assertRebuild($website, $customer);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->once())
                ->method('delete')
                ->with($customer, $website);

            $this->priceListCollectionProvider->expects($this->never())
                ->method('getPriceListsByCustomer');
            $this->combinedPriceListRepository->expects($this->never())
                ->method('updateCombinedPriceListConnection');
        }

        $this->builder->buildForCustomersWithoutGroupAndFallbackToGroup($website, $force);
        $this->builder->buildForCustomersWithoutGroupAndFallbackToGroup($website, $force);
    }

    /**
     * @return array
     */
    public function buildDataProviderByCustomerGroup()
    {
        return [
            [
                'priceListByCustomer' => null,
                'force' => true
            ],
            [
                'priceListByCustomer' => null,
                'force' => false
            ],
            [
                'priceListByCustomer' => new PriceListToCustomer(),
                'force' => true
            ],
            [
                'priceListByCustomer' => new PriceListToCustomer(),
                'force' => false
            ]
        ];
    }

    public function testRebuildForCustomerWithFallbackCplUsageWithoutFallback()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $forceTimestamp = null;
        $fallback = false;

        $this->configureTransactionWrappingForOneCall();

        $this->configurePriceListToEntityRepositoryMock($customer, $website);
        $this->configureFallbackRepositoryMock($customer, $website, $fallback);

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
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->never())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website);
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containMergeDisallowed');
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containScheduled');

        $relation = new CombinedPriceListToCustomer();
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customer, $forceTimestamp);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsageNoFallbackPriceLists()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $forceTimestamp = null;
        $fallback = true;

        $this->configureTransactionWrappingForOneCall();

        $this->configurePriceListToEntityRepositoryMock($customer, $website);
        $this->configureFallbackRepositoryMock($customer, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $pl3 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 3]), true);
        $priceListCollection = [$pl1, $pl2, $pl3];
        $fallbackCollection = null;

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->willReturn($combinedPriceList);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containMergeDisallowed');
        $this->priceListCollectionProvider->expects($this->never())
            ->method('containScheduled');

        $relation = new CombinedPriceListToCustomer();
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customer, $forceTimestamp);
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
        $website = new Website();
        $customer = new Customer();
        $customerGroup = new CustomerGroup();
        $customer->setGroup($customerGroup);
        $fallback = true;
        $forceTimestamp = null;

        $this->configureTransactionWrappingForOneCall();

        $this->configureFallbackRepositoryMock($customer, $website, $fallback);
        $this->configurePriceListToEntityRepositoryMock($customer, $website);

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
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containMergeDisallowed')
            ->with($priceListCollection)
            ->willReturn($containMergeDisallowed);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containScheduled')
            ->with($priceListCollection)
            ->willReturn($containScheduled);

        $relation = new CombinedPriceListToCustomer();
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customer, $forceTimestamp);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsageUnsupportedStrategy()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $forceTimestamp = null;
        $fallback = true;

        $this->configureTransactionWrappingForOneCall();

        $this->configurePriceListToEntityRepositoryMock($customer, $website);
        $this->configureFallbackRepositoryMock($customer, $website, $fallback);

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
            ['id' => 5, 'pricesCalculated' => true]
        );
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceList')
            ->withConsecutive(
                [$priceListCollection],
                [$fallbackCollection]
            )
            ->willReturnOnConsecutiveCalls($combinedPriceList, $fallbackLevelCpl);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containScheduled')
            ->with($priceListCollection)
            ->willReturn(false);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containMergeDisallowed')
            ->with($priceListCollection)
            ->willReturn(false);

        $relation = new CombinedPriceListToCustomer();
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
            ->willReturn($relation);

        $this->combiningStrategy->expects($this->once())
            ->method('combinePrices')
            ->with($combinedPriceList, [], $forceTimestamp);

        $this->builder->build($website, $customer, $forceTimestamp);
    }

    public function testRebuildForCustomerGroupWithFallbackCplUsage()
    {
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $forceTimestamp = null;
        $fallback = true;

        $this->configureTransactionWrappingForOneCall();

        $this->configurePriceListToEntityRepositoryMock($customer, $website);
        $this->configureFallbackRepositoryMock($customer, $website, $fallback);

        $pl1 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true);
        $pl2 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 2]), true);
        $pl3 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 3]), true);
        $pl4 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 4]), true);
        $pl5 = new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 5]), true);
        $priceListCollection = [$pl1, $pl2, $pl3, $pl4, $pl5];
        $fallbackCollection = [$pl3, $pl4, $pl5];

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $fallbackLevelCpl = $this->getEntity(CombinedPriceList::class, ['id' => 2, 'pricesCalculated' => true]);
        $this->combinedPriceListProvider->expects($this->any())
            ->method('getCombinedPriceList')
            ->withConsecutive(
                [$priceListCollection],
                [$fallbackCollection]
            )
            ->willReturnOnConsecutiveCalls($combinedPriceList, $fallbackLevelCpl);

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);
        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
            ->willReturn($fallbackCollection);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containMergeDisallowed')
            ->with($priceListCollection)
            ->willReturn(false);
        $this->priceListCollectionProvider->expects($this->any())
            ->method('containScheduled')
            ->with($priceListCollection)
            ->willReturn(false);

        $relation = new CombinedPriceListToCustomer();
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $relation->setPriceList($combinedPriceList);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
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

        $builder = new CustomerCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->cplScheduleResolver,
            $strategyRegister,
            $this->triggerHandler
        );
        $this->configureBuilderClasses($builder);

        $builder->build($website, $customer, $forceTimestamp);
    }

    protected function assertRebuild(Website $website, Customer $customer)
    {
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->willReturn($combinedPriceList);

        $relation = new CombinedPriceListToCustomer();
        $relation->setPriceList($combinedPriceList);
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
            ->willReturn($relation);
    }

    protected function configurePriceListToEntityRepositoryMock(Customer $customer, Website $website)
    {
        $this->priceListToEntityRepository->expects($this->any())
            ->method('hasAssignedPriceLists')
            ->with($website, $customer)
            ->willReturn(true);
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @param bool $hasFallback
     */
    protected function configureFallbackRepositoryMock(
        Customer $customer,
        Website $website,
        $hasFallback = true
    ) {
        $this->fallbackRepository->expects($this->once())
            ->method('hasFallbackOnNextLevel')
            ->with($website, $customer)
            ->willReturn($hasFallback);
    }
}
