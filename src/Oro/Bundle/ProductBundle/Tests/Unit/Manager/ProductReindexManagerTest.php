<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Manager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductReindexManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcher;

    /** @var ProductReindexManager */
    private $reindexManager;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $this->reindexManager = new ProductReindexManager($this->eventDispatcher);
    }

    public function testTriggerReindexationNoProducts()
    {
        $productIds = [];
        $websiteId = 777;

        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->reindexManager->triggerReindexationRequestEvent($productIds, $websiteId);
    }

    public function testTriggerReindexationRequestEvent()
    {
        $productIds = [1, 2, 3];
        $websiteId = 777;
        $event = new ReindexationRequestEvent([Product::class], [$websiteId], $productIds);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationRequestEvent::EVENT_NAME, $event);
        $this->reindexManager->triggerReindexationRequestEvent($productIds, $websiteId);
    }
}
