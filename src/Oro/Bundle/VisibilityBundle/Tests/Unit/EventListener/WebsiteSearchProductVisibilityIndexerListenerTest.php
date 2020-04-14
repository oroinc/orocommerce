<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\VisibilityBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class WebsiteSearchProductVisibilityIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebsiteSearchProductVisibilityIndexerListener
     */
    private $listener;

    /**
     * @var ProductVisibilityIndexer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $visibilityIndexer;

    /**
     * @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteContextManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->visibilityIndexer = $this->getMockBuilder(ProductVisibilityIndexer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductVisibilityIndexerListener(
            $this->visibilityIndexer,
            $this->websiteContextManager
        );
    }

    public function testOnWebsiteSearchIndex()
    {
        $websiteId = 1;
        $context =  [AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId];
        $event = new IndexEntityEvent(\stdClass::class, [], $context);

        $this->websiteContextManager
            ->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $this->visibilityIndexer
            ->expects($this->once())
            ->method('addIndexInfo')
            ->with($event, $websiteId);

        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * No exception (it may broke queue) listener will return safely
     */
    public function testOnWebsiteSearchIndexWhenWebsiteIdIsNotInContext()
    {
        $event = new IndexEntityEvent(\stdClass::class, [], []);

        $this->visibilityIndexer
            ->expects($this->never())
            ->method('addIndexInfo');

        $this->listener->onWebsiteSearchIndex($event);
    }
}
