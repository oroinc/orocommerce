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
            $this->garbageCollector,
            $this->cplScheduleResolver,
            $this->priceResolver
        );
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $this->builder->setFallbackClassName($this->fallbackClass);
    }

    /**
     * @dataProvider buildDataProvider
     * @param PriceListToAccount $priceListByAccount
     */
    public function testBuild($priceListByAccount)
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
            $this->fallbackRepository->expects($this->exactly($callExpects))->method('findOneBy');
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');
            $this->fallbackRepository->expects($this->never())->method('findOneBy');
            
            $this->assertRebuild($website, $account);
        }
        $this->builder->build($website, $account);
        $this->builder->build($website, $account);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'priceListByAccount' => null
            ],
            [
                'priceListByAccount' => null
            ],
            [
                'priceListByAccount' => new PriceListToAccount()
            ],
            [
                'force' => false,
                'priceListByAccount' => new PriceListToAccount()
            ],
        ];
    }

    /**
     * @dataProvider buildDataProviderByAccountGroup
     * @param PriceListToAccountGroup $priceListByAccountGroup
     * @param bool $force
     */
    public function testBuildByAccountGroup($priceListByAccountGroup, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $accountGroup = new AccountGroup();
        $account = new Account();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByAccountGroup);

        $fallback = $force ? null : PriceListAccountFallback::ACCOUNT_GROUP;

        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getAccountIteratorByDefaultFallback')
            ->with($accountGroup, $website, $fallback)
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

            $this->assertRebuild($website, $account);
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
            [
                'priceListByAccountGroup' => null,
                'force' => true
            ],
            [
                'priceListByAccountGroup' => null,
                'force' => false
            ],
            [
                'priceListByAccountGroup' => new PriceListToAccountGroup(),
                'force' => true
            ],
            [
                'priceListByAccountGroup' => new PriceListToAccountGroup(),
                'force' => false
            ]
        ];
    }

    /**
     * @param Website $website
     * @param Account $account
     */
    protected function assertRebuild(Website $website, Account $account)
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
            ->with($priceListCollection)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website, $account);
    }
}
