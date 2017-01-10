<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CombinedPriceListTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{

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
     * @var MinimalProductPriceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var array
     */
    protected $events = [];

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(MinimalProductPriceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->with(MinimalProductPrice::class)->willReturn($this->repository);

        $this->registry = $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock();
        $this->registry->method('getManagerForClass')->willReturn($manager);

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->eventDispatcher->method('dispatch')->willReturnCallback(
            function ($eventName, $event) {
                $this->events[$eventName][] = $event;
            }
        );
        $this->triggerHandler = new CombinedPriceListTriggerHandler($this->registry, $this->eventDispatcher);
        $this->events = [];
    }

    /**
     * @dataProvider processDataProvider
     * @param CombinedPriceList $combinedPriceList
     * @param array $productIds
     * @param array $expectedEvents
     * @param Website|null $website
     */
    public function testProcess(
        CombinedPriceList $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        Website $website = null
    ) {
        $this->repository->method('getProductIdsByPriceLists')->willReturn($productIds);
        $this->triggerHandler->process($combinedPriceList, $website);
        $this->assertEquals($expectedEvents, $this->events);
    }

    /**
     * @dataProvider processDataProvider
     * @param CombinedPriceList $combinedPriceList
     * @param array $productIds
     * @param array $expectedEvents
     * @param Website|null $website
     */
    public function testProcessWithCommit(
        CombinedPriceList $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        Website $website = null
    ) {
        $this->repository->method('getProductIdsByPriceLists')->willReturn($productIds);

        $this->triggerHandler->startCollect();
        $this->triggerHandler->process($combinedPriceList, $website);
        $this->assertEmpty($this->events);
        $this->triggerHandler->commit();
        $this->assertEquals($expectedEvents, $this->events);
    }

    /**
     * @dataProvider processDataProvider
     * @param CombinedPriceList $combinedPriceList
     * @param array $productIds
     * @param array $expectedEvents
     * @param Website|null $website
     */
    public function testMassProcess(
        CombinedPriceList $combinedPriceList,
        array $productIds,
        array $expectedEvents,
        Website $website = null
    ) {
        $this->repository->method('getProductIdsByPriceLists')->willReturn($productIds);
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
        $combinedPriceList = new CombinedPriceList();

        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn(1);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);

        return [
            [
                'cpl' => $combinedPriceList,
                'productIds' => [
                    $product->getId(),
                ],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME =>
                        [new ReindexationRequestEvent([Product::class], [], [$product->getId()])]
                ],
                'website' => null,
            ],
            [
                'cpl' => $combinedPriceList,
                'productIds' => [
                    $product->getId()
                ],
                'events' => [
                    ReindexationRequestEvent::EVENT_NAME =>
                        [new ReindexationRequestEvent([Product::class], [$website->getId()], [$product->getId()])]
                ],
                'website' => $website,
            ],
        ];
    }
}
