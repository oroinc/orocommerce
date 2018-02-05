<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class CombinedPriceListTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;
    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var CombinedProductPriceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var array
     */
    protected $events = [];

    public function setUp()
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
            function ($eventName, $event) {
                $this->events[$eventName][] = $event;
            }
        );

        $this->triggerHandler = new CombinedPriceListTriggerHandler($this->registry, $this->eventDispatcher);
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param array $combinedPriceList
     * @param array $productIds
     * @param array $expectedEvents
     * @param array|null $website
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
     *
     * @param array $combinedPriceList
     * @param array $productIds
     * @param array $expectedEvents
     * @param array|null $website
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
     *
     * @param array $combinedPriceList
     * @param array $productIds
     * @param array $expectedEvents
     * @param array|null $website
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
}
