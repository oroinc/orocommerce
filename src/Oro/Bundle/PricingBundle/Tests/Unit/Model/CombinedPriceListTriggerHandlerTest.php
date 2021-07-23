<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CombinedPriceListTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var CombinedProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var array
     */
    protected $events = [];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CombinedProductPriceRepository::class);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(CombinedProductPrice::class)
            ->willReturn($this->repository);

        $this->registry = $this->createMock(Registry::class);
        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->eventDispatcher->method('dispatch')->willReturnCallback(
            function ($event, $eventName) {
                $this->events[$eventName][] = $event;
            }
        );

        $this->triggerHandler = new CombinedPriceListTriggerHandler($this->registry, $this->eventDispatcher);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        array $website = null
    ) {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, $combinedPriceList);
        if ($website) {
            $website = $this->getEntity(Website::class, $website);
        }

        $this->repository
            ->expects($this->once())
            ->method('getProductIdsByPriceLists')
            ->willReturn($productIds);

        $this->triggerHandler->process($combinedPriceList, $website);
        $this->assertEquals($expectedEvents, $this->events);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcessWithCommit(
        array $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        array $website = null
    ) {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, $combinedPriceList);
        if ($website) {
            $website = $this->getEntity(Website::class, $website);
        }

        $this->repository
            ->expects($this->once())
            ->method('getProductIdsByPriceLists')
            ->willReturn($productIds);

        $this->triggerHandler->startCollect();
        $this->triggerHandler->process($combinedPriceList, $website);
        $this->assertEmpty($this->events);
        $this->triggerHandler->commit();
        $this->assertEquals($expectedEvents, $this->events);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcessWithNestedCommits(
        array $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        array $website = null
    ) {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, $combinedPriceList);
        if ($website) {
            $website = $this->getEntity(Website::class, $website);
        }

        $this->repository
            ->expects($this->once())
            ->method('getProductIdsByPriceLists')
            ->willReturn($productIds);
        $this->assertEmpty($this->events);
        $this->triggerHandler->startCollect();
        $this->triggerHandler->startCollect();
        $this->triggerHandler->process($combinedPriceList, $website);
        $this->triggerHandler->commit();
        $this->assertEmpty($this->events);
        $this->triggerHandler->commit();
        $this->assertEquals($expectedEvents, $this->events);
    }

    public function testProcessWithRollback()
    {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, ['id' => 1001]);
        $website = $this->getEntity(Website::class, ['id' => 1001]);

        $this->repository->expects($this->never())
            ->method('getProductIdsByPriceLists');

        $this->triggerHandler->startCollect();
        $this->triggerHandler->process($combinedPriceList, $website);
        $this->assertEmpty($this->events);
        $this->triggerHandler->rollback();
        $this->triggerHandler->commit();
        $this->assertEmpty($this->events);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testMassProcess(
        array $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        array $website = null
    ) {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, $combinedPriceList);
        if ($website) {
            $website = $this->getEntity(Website::class, $website);
        }
        $this->repository
            ->expects($this->once())
            ->method('getProductIdsByPriceLists')
            ->willReturn($productIds);

        $this->triggerHandler->startCollect();
        $this->triggerHandler->massProcess([$combinedPriceList], $website);
        $this->triggerHandler->commit();
        $this->assertEquals($expectedEvents, $this->events);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            [
                'cpl' => ['id' => 1],
                'productIds' => [1, 2],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME =>
                        [new ReindexationRequestEvent([Product::class], [], [1, 2])]
                ],
                'website' => null,
            ],
            [
                'cpl' => ['id' => 1],
                'productIds' => [1, 2],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME =>
                        [new ReindexationRequestEvent([Product::class], [1], [1, 2])]
                ],
                'website' => ['id' => 1],
            ],
        ];
    }

    /**
     * @dataProvider processByProductDataProvider
     *
     * @param array $combinedPriceList
     * @param array $expectedEvents
     * @param array|null $products
     * @param array|null $website
     * @param array $productIds
     */
    public function testProcessByProduct(
        array $combinedPriceList,
        array $expectedEvents,
        array $products = [],
        array $website = null,
        array $productIds = []
    ) {
        $combinedPriceList = $this->getEntity(CombinedPriceList::class, $combinedPriceList);
        if ($website) {
            $website = $this->getEntity(Website::class, $website);
        }

        $this->repository->expects($this->any())
            ->method('getProductIdsByPriceLists')
            ->willReturn($productIds);

        $this->triggerHandler->startCollect();
        $this->triggerHandler->processByProduct($combinedPriceList, $products, $website);
        $this->triggerHandler->commit();
        $this->assertEquals($expectedEvents, $this->events);
    }

    /**
     * @return array
     */
    public function processByProductDataProvider()
    {
        return [
            [
                'cpl' => ['id' => 1001],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME => [
                        new ReindexationRequestEvent([Product::class], [3003], [2002])
                    ]
                ],
                'products' => [2002],
                'website' => ['id' => 3003],
            ],
            [
                'cpl' => ['id' => 1001],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME => [
                        new ReindexationRequestEvent([Product::class], [], [2002])
                    ]
                ],
                'products' => [2002],
                'website' => null,
            ],
            [
                'cpl' => ['id' => 1001],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME => [
                        new ReindexationRequestEvent([Product::class], [3003], [4004, 5005])
                    ]
                ],
                'products' => [],
                'website' => ['id' => 3003],
                'productIds' => [4004, 5005],
            ],
        ];
    }

    public function testProcessForWebsites()
    {
        $cpl1ProductIds = [1, 2];
        $cpl2ProductIds = [1, 3];
        $this->repository
            ->expects($this->exactly(2))
            ->method('getProductIdsByPriceLists')
            ->willReturnOnConsecutiveCalls($cpl1ProductIds, $cpl2ProductIds);

        $this->triggerHandler->startCollect();

        /** Process CPL for all websites */
        $combinedPriceList1 = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $this->triggerHandler->process($combinedPriceList1);

        /** Process CPL for website1 */
        $website1Id = 1;
        $website1   = $this->getEntity(Website::class, ['id' => $website1Id]);
        $combinedPriceList2 = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $this->triggerHandler->process($combinedPriceList2, $website1);

        $this->assertEmpty($this->events);
        $this->triggerHandler->commit();

        $this->assertEquals(
            [
                ReindexationRequestEvent::EVENT_NAME => [
                    new ReindexationRequestEvent([Product::class], [], $cpl1ProductIds),
                    new ReindexationRequestEvent([Product::class], [$website1Id], [3])
                ]
            ],
            $this->events
        );
    }
}
