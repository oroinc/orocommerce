<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
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
     * @param bool $force
     * @param PriceListToAccount $priceListByAccount
     */
    public function testBuild($force, $priceListByAccount)
    {
        $website = new Website();
        $account = new Account();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByAccount);
        $callExpects = 1;
        if ($force) {
            $callExpects = 2;
        }
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

            $this->assertRebuild($force, $website, $account);
        }
        $this->builder->build($website, $account, $force);
        $this->builder->build($website, $account, $force);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            ['force' => true, 'priceListByAccount' => null],
            ['force' => false, 'priceListByAccount' => null],
            ['force' => true, 'priceListByAccount' => new PriceListToAccount()],
            ['force' => false, 'priceListByAccount' => new PriceListToAccount()]
        ];
    }

    /**
     * @dataProvider buildDataProviderByAccountGroup
     * @param boolean $force
     * @param PriceListToAccountGroup $priceListByAccountGroup
     */
    public function testBuildByAccountGroup($force, $priceListByAccountGroup)
    {
        $callExpects = 1;
        if ($force) {
            $callExpects = 2;
        }
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

            $this->assertRebuild($force, $website, $account);
        }

        $this->builder->buildByAccountGroup($website, $accountGroup, $force);
        $this->builder->buildByAccountGroup($website, $accountGroup, $force);
    }

    /**
     * @return array
     */
    public function buildDataProviderByAccountGroup()
    {
        return [
            ['force' => true, 'priceListByAccountGroup' => null],
            ['force' => false, 'priceListByAccountGroup' => null],
            ['force' => true, 'priceListByAccountGroup' => new PriceListToAccountGroup()],
            ['force' => false, 'priceListByAccountGroup' => new PriceListToAccountGroup()]
        ];
    }

    /**
     * @param bool $force
     * @param Website $website
     * @param Account $account
     */
    protected function assertRebuild($force, Website $website, Account $account)
    {
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $callExpects = 1;
        if ($force) {
            $callExpects = 2;
        }
        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByAccount')
            ->with($account, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListCollection, $force)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $website, $account);
    }
}
