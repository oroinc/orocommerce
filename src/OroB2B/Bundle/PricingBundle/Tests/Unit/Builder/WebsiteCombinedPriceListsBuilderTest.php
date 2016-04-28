<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    /**
     * @var WebsiteCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var AccountGroupCombinedPriceListsBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountGroupBuilder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->accountGroupBuilder = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new WebsiteCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->garbageCollector
        );
        $this->builder->setAccountGroupCombinedPriceListsBuilder($this->accountGroupBuilder);
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
    }

    /**
     * @dataProvider buildDataProvider
     * @param int $behavior
     * @param PriceListToWebsite $priceListByWebsite
     * @param bool $force
     */
    public function testBuildForAll($behavior, $priceListByWebsite, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByWebsite);

        $fallback = $force ? null : PriceListWebsiteFallback::CONFIG;

        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getWebsiteIteratorByDefaultFallback')
            ->with($fallback)
            ->will($this->returnValue([$website]));
        $this->garbageCollector->expects($this->never())
            ->method($this->anything());

        if (!$priceListByWebsite) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($behavior, $website);
        }

        $this->builder->build(null, $force);
        $this->builder->build(null, $force);
    }

    /**
     * @dataProvider buildDataProvider
     * @param int $behavior
     * @param PriceListToWebsite $priceListByWebsite
     * @param bool $force
     */
    public function testBuildForWebsite($behavior, $priceListByWebsite, $force = false)
    {
        $callExpects = 1;
        $website = new Website();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByWebsite);
        $this->priceListToEntityRepository->expects($this->never())
            ->method('getWebsiteIteratorByDefaultFallback');
        $this->garbageCollector->expects($this->exactly($callExpects))
            ->method('cleanCombinedPriceLists');

        if (!$priceListByWebsite) {
            $this->combinedPriceListToEntityRepository
                ->expects($this->exactly($callExpects))
                ->method('delete')
                ->with($website);
        } else {
            $this->combinedPriceListToEntityRepository
                ->expects($this->never())
                ->method('delete');

            $this->assertRebuild($behavior, $website);
        }

        $this->builder->build($website, $force);
        $this->builder->build($website, $force);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'priceListByWebsite' => null,
                'force' => true,
            ],
            [
                'priceListByWebsite' => null,
                'force' => false,
            ],
            [
                'priceListByWebsite' => new PriceListToWebsite(),
                'force' => true,
            ],
            [

                'priceListByWebsite' => new PriceListToWebsite(),
                'force' => true
            ]
        ];
    }

    /**
     * @param int $behavior
     * @param Website $website
     */
    protected function assertRebuild($behavior, Website $website)
    {
        $callExpects = 1;
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $this->priceListCollectionProvider->expects($this->exactly($callExpects))
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->exactly($callExpects))
            ->method('getCombinedPriceList')
            ->with($priceListCollection, $behavior)
            ->will($this->returnValue($combinedPriceList));

        $this->combinedPriceListRepository->expects($this->exactly($callExpects))
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $website);

        $this->accountGroupBuilder->expects($this->exactly($callExpects))
            ->method('build')
            ->with($website, null, $behavior);
    }
}
