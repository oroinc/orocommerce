<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\AccountCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
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
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $force
     */
    public function testBuild($force)
    {
        $website = new Website();
        $account = new Account();

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $this->assertRebuild($force, $website, $account);

        $this->builder->build($website, $account, $force);
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $force
     */
    public function testBuildByAccountGroup($force)
    {
        $website = new Website();
        $accountGroup = new AccountGroup();
        $account = new Account();

        $this->priceListToEntityRepository->expects($this->once())
            ->method('getAccountIteratorByFallback')
            ->with($accountGroup, $website, PriceListAccountFallback::ACCOUNT_GROUP)
            ->will($this->returnValue([$account]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        $this->assertRebuild($force, $website, $account);

        $this->builder->buildByAccountGroup($website, $accountGroup, $force);
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

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByAccount')
            ->with($account, $website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection, $force)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $website, $account);
    }
}
