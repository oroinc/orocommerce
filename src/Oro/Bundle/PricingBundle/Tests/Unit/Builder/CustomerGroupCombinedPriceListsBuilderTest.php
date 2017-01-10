<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CustomerGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerGroupCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
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

        $this->customerBuilder = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new CustomerGroupCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $this->priceResolver,
            $this->triggerHandler
        );
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setCustomerCombinedPriceListsBuilder($this->customerBuilder);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $this->builder->setFallbackClassName($this->fallbackClass);
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

        if (!$priceListByCustomerGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($customerGroup, $website);
            $this->fallbackRepository->expects($this->exactly($callExpects))->method('findOneBy');
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');
            $this->fallbackRepository->expects($this->never())->method('findOneBy');

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
}
