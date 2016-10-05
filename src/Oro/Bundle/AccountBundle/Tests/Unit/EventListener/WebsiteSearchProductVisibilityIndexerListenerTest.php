<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\AccountBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductVisibilityIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchProductVisibilityIndexerListener
     */
    private $listener;

    /**
     * @var ProductVisibilityIndexer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $visibilityIndexer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->visibilityIndexer = $this->getMockBuilder(ProductVisibilityIndexer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductVisibilityIndexerListener($this->visibilityIndexer);
    }

    public function testOnWebsiteSearchIndex()
    {
        $websiteId = 1;
        $event = new IndexEntityEvent(Product::class, [], [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $websiteId]);

        $this->visibilityIndexer
            ->expects($this->once())
            ->method('addIndexInfo')
            ->with($event, $websiteId);

        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Website id is absent in context
     */
    public function testOnWebsiteSearchIndexWhenWebsiteIdIsNotInContext()
    {
        $event = new IndexEntityEvent(Product::class, [], []);

        $this->visibilityIndexer
            ->expects($this->never())
            ->method('addIndexInfo');

        $this->listener->onWebsiteSearchIndex($event);
    }
}
