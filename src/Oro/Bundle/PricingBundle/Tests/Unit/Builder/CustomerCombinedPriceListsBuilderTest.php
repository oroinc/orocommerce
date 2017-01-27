<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
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
            $this->priceResolver,
            $this->triggerHandler
        );
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $this->builder->setFallbackClassName($this->fallbackClass);
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
        if (!$priceListByCustomer) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($customer, $website);
            $this->fallbackRepository->expects($this->exactly($callExpects))->method('findOneBy');
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');
            $this->fallbackRepository->expects($this->never())->method('findOneBy');
            
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
}
