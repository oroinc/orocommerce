<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\PricingBundle\Builder\CustomerGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteCombinedPriceListsBuilderTest extends AbstractCombinedPriceListsBuilderTest
{
    /**
     * @var WebsiteCombinedPriceListsBuilder
     */
    protected $builder;

    /**
     * @var CustomerGroupCombinedPriceListsBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerGroupBuilder;

    /**
     * @return string
     */
    protected function getPriceListToEntityRepositoryClass()
    {
        return PriceListToWebsiteRepository::class;
    }

    /**
     * @return string
     */
    protected function getPriceListFallbackRepositoryClass()
    {
        return PriceListWebsiteFallbackRepository::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerGroupBuilder = $this->createMock(CustomerGroupCombinedPriceListsBuilder::class);

        $this->builder = new WebsiteCombinedPriceListsBuilder(
            $this->registry,
            $this->priceListCollectionProvider,
            $this->combinedPriceListProvider,
            $this->cplScheduleResolver,
            $this->strategyRegister,
            $this->triggerHandler
        );
        $this->builder->setCustomerGroupCombinedPriceListsBuilder($this->customerGroupBuilder);
        $this->builder->setPriceListToEntityClassName($this->priceListToEntityClass);
        $this->builder->setCombinedPriceListClassName($this->combinedPriceListClass);
        $this->builder->setCombinedPriceListToEntityClassName($this->combinedPriceListToEntityClass);
        $this->builder->setFallbackClassName($this->fallbackClass);
    }

    public function testBuildWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test exception');

        $website = new Website();
        $this->priceListToEntityRepository
            ->expects($this->once())
            ->method('hasAssignedPriceLists')
            ->willReturn(true);

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('beginTransaction');

        $this->combinedPriceListEm
            ->expects($this->never())
            ->method('commit');

        $this->combinedPriceListEm
            ->expects($this->once())
            ->method('rollback');

        $this->priceListToEntityRepository
            ->expects($this->never())
            ->method('getWebsiteIteratorWithDefaultFallback');

        $this->combinedPriceListToEntityRepository
            ->expects($this->never())
            ->method('delete');

        $this->fallbackRepository
            ->expects($this->never())
            ->method('hasFallbackOnNextLevel');

        $this->priceListCollectionProvider
            ->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willThrowException(new \Exception('test exception'));

        $this->builder->build($website, null);
    }

    /**
     * @dataProvider buildDataProvider
     * @param PriceListToWebsite $priceListByWebsite
     * @param bool $hasFallback
     * @param bool $force
     */
    public function testBuildForAll($priceListByWebsite, $hasFallback = true, $force = false)
    {
        $website = new Website();
        $this->priceListToEntityRepository->expects($this->atLeastOnce())
            ->method('getWebsiteIteratorWithDefaultFallback')
            ->willReturn([$website]);

        $this->assertBuilderCalls($priceListByWebsite, $hasFallback, $website);

        $this->builder->build(null, $force);
        $this->builder->build(null, $force);
    }

    /**
     * @dataProvider buildDataProvider
     * @param PriceListToWebsite|null $priceListByWebsite
     * @param bool $hasFallback
     * @param bool $force
     */
    public function testBuildForWebsite($priceListByWebsite, $hasFallback = true, $force = false)
    {
        $website = new Website();
        $this->priceListToEntityRepository->expects($this->never())
            ->method('getWebsiteIteratorWithDefaultFallback');

        $this->assertBuilderCalls($priceListByWebsite, $hasFallback, $website);

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
                'hasFallback' => true,
                'force' => true,
            ],
            [
                'priceListByWebsite' => null,
                'hasFallback' => false,
                'force' => true,
            ],
            [
                'priceListByWebsite' => null,
                'hasFallback' => true,
                'force' => false,
            ],
            [
                'priceListByWebsite' => new PriceListToWebsite(),
                'hasFallback' => true,
                'force' => true,
            ],
            [

                'priceListByWebsite' => new PriceListToWebsite(),
                'hasFallback' => false,
                'force' => true
            ]
        ];
    }

    protected function assertRebuild(Website $website)
    {
        $priceListCollection = [$this->getPriceListSequenceMember()];
        $combinedPriceList = new CombinedPriceList();

        $this->priceListCollectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($priceListCollection);

        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($priceListCollection)
            ->willReturn($combinedPriceList);

        $relation = new CombinedPriceListToWebsite();
        $relation->setPriceList($combinedPriceList);
        $relation->setWebsite($website);
        $this->combinedPriceListRepository->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($combinedPriceList, $combinedPriceList, $website)
            ->willReturn($relation);

        $this->customerGroupBuilder->expects($this->once())
            ->method('build')
            ->with($website, null);
    }

    /**
     * @param PriceListToWebsite|null $priceListByWebsite
     * @param bool $hasFallback
     * @param Website $website
     */
    protected function assertBuilderCalls($priceListByWebsite, $hasFallback, Website $website): void
    {
        $this->priceListToEntityRepository
            ->expects($this->any())
            ->method('hasAssignedPriceLists')
            ->willReturn($priceListByWebsite !== null);

        $this->configureTransactionWrappingForOneCall();

        $expectation = !$priceListByWebsite ? $this->once() : $this->never();
        $this->combinedPriceListToEntityRepository->expects($expectation)
            ->method('delete')
            ->with($website);
        $this->fallbackRepository->expects($this->any())
            ->method('hasFallbackOnNextLevel')
            ->willReturn($hasFallback);

        if (!$hasFallback || $priceListByWebsite) {
            $this->assertRebuild($website);
        }
    }
}
