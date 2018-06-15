<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyFallbackAwareInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

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
        return 'Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->builder = new CustomerCombinedPriceListsBuilder(
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
     * @param CustomerCombinedPriceListsBuilder $builder
     */
    protected function configureBuilderClasses(CustomerCombinedPriceListsBuilder $builder)
    {
        $builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $builder->setFallbackClassName($this->fallbackClass);
    }

    /**
     * @dataProvider buildDataProvider
     * @param PriceListToCustomer $priceListByCustomer
     */
    public function testBuild($priceListByCustomer)
    {
        $website = new Website();
        $customer = new Customer();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByCustomer);
        $callExpects = 1;
        $this->garbageCollector->expects($this->exactly($callExpects))
            ->method('cleanCombinedPriceLists');

        $this->fallbackRepository->expects($this->exactly($callExpects))->method('findOneBy');
        if (!$priceListByCustomer) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($customer, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($website, $customer);
        }
        $this->builder->build($website, $customer);
        $this->builder->build($website, $customer);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'priceListByCustomer' => null
            ],
            [
                'priceListByCustomer' => null
            ],
            [
                'priceListByCustomer' => new PriceListToCustomer()
            ],
            [
                'force' => false,
                'priceListByCustomer' => new PriceListToCustomer()
            ],
        ];
    }

    /**
     * @dataProvider buildDataProviderByCustomerGroup
     * @param PriceListToCustomerGroup $priceListByCustomerGroup
     * @param bool $force
     */
    public function testBuildByCustomerGroup($priceListByCustomerGroup, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByCustomerGroup);

        $fallback = $force ? null : PriceListCustomerFallback::ACCOUNT_GROUP;

        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getCustomerIteratorByDefaultFallback')
            ->with($customerGroup, $website, $fallback)
            ->will($this->returnValue([$customer]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        if (!$priceListByCustomerGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($customer, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($website, $customer);
        }

        $this->builder->buildByCustomerGroup($website, $customerGroup, $force);
        $this->builder->buildByCustomerGroup($website, $customerGroup, $force);
    }

    /**
     * @return array
     */
    public function buildDataProviderByCustomerGroup()
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
                'force' => true
            ],
            [
                'priceListByCustomerGroup' => new PriceListToCustomerGroup(),
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
        $fallback = $this->getEntity(PriceListCustomerFallback::class);

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
        $fallback = null;

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
        $fallback = null;
        $forceTimestamp = null;

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
        $fallback = null;

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
        $fallback = null;

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
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $strategyRegister,
            $this->triggerHandler
        );
        $this->configureBuilderClasses($builder);

        $builder->build($website, $customer, $forceTimestamp);
    }

    /**
     * @param Website $website
     * @param Customer $customer
     */
    protected function assertRebuild(Website $website, Customer $customer)
    {
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $callExpects = 1;
        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->will($this->returnValue($combinedPriceList));

        $relation = new CombinedPriceListToCustomer();
        $relation->setPriceList($combinedPriceList);
        $relation->setWebsite($website);
        $relation->setCustomer($customer);
        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $customer)
            ->willReturn($relation);
    }

    /**
     * @param Customer $customer
     * @param Website $website
     */
    protected function configurePriceListToEntityRepositoryMock(Customer $customer, Website $website)
    {
        $this->priceListToEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->with(
                [
                    'customer' => $customer,
                    'website' => $website
                ]
            )
            ->willReturn(new PriceListToCustomerGroup());
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @param PriceListCustomerFallback|null $fallback
     */
    protected function configureFallbackRepositoryMock(
        Customer $customer,
        Website $website,
        PriceListCustomerFallback $fallback = null
    ) {
        $this->fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customer' => $customer,
                    'website' => $website,
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY
                ]
            )
            ->willReturn($fallback);
    }
}
