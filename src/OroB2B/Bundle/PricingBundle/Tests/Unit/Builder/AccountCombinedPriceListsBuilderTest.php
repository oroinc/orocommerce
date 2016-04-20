<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    /**
     * @var AccountCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->builder = new AccountCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector
        );
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
    }

    /**
     * @dataProvider buildDataProvider
     * @param int $behavior
     * @param PriceListToAccount $priceListByAccount
     */
    public function testBuild($behavior, $priceListByAccount)
    {
        $website = new Website();
        $account = new Account();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByAccount);
        $callExpects = 1;
        $this->garbageCollector->expects($this->exactly($callExpects))
            ->method('cleanCombinedPriceLists');
        if (!$priceListByAccount) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($account, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($behavior, $website, $account);
        }
        $this->builder->build($website, $account, $behavior);
        $this->builder->build($website, $account, $behavior);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByAccount' => null
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_DEFAULT,
                'priceListByAccount' => null
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByAccount' => new PriceListToAccount()
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_DEFAULT,
                'priceListByAccount' => new PriceListToAccount()
            ],
        ];
    }

    /**
     * @dataProvider buildDataProviderByAccountGroup
     * @param boolean $behavior
     * @param PriceListToAccountGroup $priceListByAccountGroup
     */
    public function testBuildByAccountGroup($behavior, $priceListByAccountGroup)
    {
        $callExpects = 1;
        $website = new Website();
        $accountGroup = new AccountGroup();
        $account = new Account();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByAccountGroup);
        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getAccountIteratorByDefaultFallback')
            ->with($accountGroup, $website, PriceListAccountFallback::ACCOUNT_GROUP)
            ->will($this->returnValue([$account]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        if (!$priceListByAccountGroup) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($account, $website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($behavior, $website, $account);
        }

        $this->builder->buildByAccountGroup($website, $accountGroup, $behavior);
        $this->builder->buildByAccountGroup($website, $accountGroup, $behavior);
    }

    /**
     * @return array
     */
    public function buildDataProviderByAccountGroup()
    {
        return [
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByAccountGroup' => null
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
                'priceListByAccountGroup' => new PriceListToAccountGroup()
            ]
        ];
    }

    /**
     * @param int $behavior
     * @param Website $website
     * @param Account $account
     */
    protected function assertRebuild($behavior, Website $website, Account $account)
    {
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $callExpects = 1;
        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByAccount')
            ->with($account, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListCollection, $behavior)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $website, $account);
    }
}
