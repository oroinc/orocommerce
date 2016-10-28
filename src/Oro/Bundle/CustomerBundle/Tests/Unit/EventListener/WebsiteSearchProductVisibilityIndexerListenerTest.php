<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\CustomerBundle\Indexer\ProductVisibilityIndexer;
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
        $event = new IndexEntityEvent(
            [],
            [
                AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId
            ]
        );

        $this->visibilityIndexer
            ->expects($this->once())
            ->method('addIndexInfo')
            ->with($event, $websiteId);

        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Required website id is not passed to context
     */
    public function testOnWebsiteSearchIndexWhenWebsiteIdIsNotInContext()
    {
        $event = new IndexEntityEvent([], []);

        $this->visibilityIndexer
            ->expects($this->never())
            ->method('addIndexInfo');

        $this->listener->onWebsiteSearchIndex($event);
    }
}
