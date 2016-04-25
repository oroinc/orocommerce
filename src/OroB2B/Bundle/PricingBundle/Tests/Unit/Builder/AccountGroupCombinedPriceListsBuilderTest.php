<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    /**
     * @var AccountGroupCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var AccountCombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountBuilder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->accountBuilder = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new AccountGroupCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector
        );
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setAccountCombinedPriceListsBuilder($this->accountBuilder);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param int $behavior
     * @param PriceListToAccountGroup $priceListByAccountGroup
     * @param bool $force
     */
    public function testBuildForAll($behavior, $priceListByAccountGroup, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $accountGroup = new AccountGroup();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByAccountGroup);

        $fallback = $force ? null : PriceListAccountGroupFallback::WEBSITE;

        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getAccountGroupIteratorByDefaultFallback')
            ->with($website, $fallback)
            ->will($this->returnValue([$accountGroup]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        if (!$priceListByAccountGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($accountGroup, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($behavior, $website, $accountGroup, $force);
        }

        $this->builder->build($website, null, $behavior, $force);
        $this->builder->build($website, null, $behavior, $force);
    }

    /**
     * @return array
     */
    public function testBuildDataProvider()
    {
        return [
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByAccountGroup' => null,
                'force' => true
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_DEFAULT,
                'priceListByAccountGroup' => null
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByAccountGroup' => new PriceListToAccountGroup()
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_DEFAULT,
                'priceListByAccountGroup' => new PriceListToAccountGroup(),
                'force' => true
            ]
        ];
    }

    /**
     * @dataProvider testBuildDataProvider
     * @param int $behavior
     * @param PriceListToAccountGroup $priceListByAccountGroup
     * @param bool $force
     */
    public function testBuildForAccountGroup($behavior, $priceListByAccountGroup, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $accountGroup = new AccountGroup();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByAccountGroup);
        $this->priceListToEntityRepository->expects($this->never())
            ->method('getAccountGroupIteratorByDefaultFallback');
        $this->garbageCollector->expects($this->exactly($callExpects))
            ->method('cleanCombinedPriceLists');

        if (!$priceListByAccountGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($accountGroup, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($behavior, $website, $accountGroup, $force);
        }

        $this->builder->build($website, $accountGroup, $behavior, $force);
        $this->builder->build($website, $accountGroup, $behavior, $force);
    }

    /**
     * @param int $behavior
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @param bool $force
     */
    protected function assertRebuild($behavior, Website $website, AccountGroup $accountGroup, $force)
    {
        $callExpects = 1;
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByAccountGroup')
            ->with($accountGroup, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListCollection, $behavior)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $website, $accountGroup);

        $this->accountBuilder->expects($this->exactly($callExpects))
            ->method('buildByAccountGroup')
            ->with($website, $accountGroup, $behavior, $force);
    }
}
