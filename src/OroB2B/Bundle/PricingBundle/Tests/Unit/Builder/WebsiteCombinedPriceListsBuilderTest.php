<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\PricingBundle\Builder\AccountGroupCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
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
     */
    public function testBuildForAll($behavior, $priceListByWebsite)
    {
        $callExpects = 1;
        $website = new Website();
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($priceListByWebsite);
        $this->priceListToEntityRepository->expects($this->exactly($callExpects))
            ->method('getWebsiteIteratorByDefaultFallback')
            ->with(PriceListWebsiteFallback::CONFIG)
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

        $this->builder->build(null, $behavior);
        $this->builder->build(null, $behavior);
    }

    /**
     * @dataProvider buildDataProvider
     * @param int $behavior
     * @param PriceListToWebsite $priceListByWebsite
     */
    public function testBuildForWebsite($behavior, $priceListByWebsite)
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

        $this->builder->build($website, $behavior);
        $this->builder->build($website, $behavior);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByWebsite' => null],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_DEFAULT,
                'priceListByWebsite' => null],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_FORCE,
                'priceListByWebsite' => new PriceListToWebsite()
            ],
            [
                'behavior' => CombinedPriceListProvider::BEHAVIOR_DEFAULT,
                'priceListByWebsite' => new PriceListToWebsite()
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
