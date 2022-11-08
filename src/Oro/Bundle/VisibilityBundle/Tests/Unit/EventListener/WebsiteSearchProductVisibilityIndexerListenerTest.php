<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\VisibilityBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class WebsiteSearchProductVisibilityIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVisibilityIndexer|\PHPUnit\Framework\MockObject\MockObject */
    private $visibilityIndexer;

    /** @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteContextManager;

    /** @var WebsiteSearchProductVisibilityIndexerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->visibilityIndexer = $this->createMock(ProductVisibilityIndexer::class);
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);

        $this->listener = new WebsiteSearchProductVisibilityIndexerListener(
            $this->visibilityIndexer,
            $this->websiteContextManager
        );
    }

    public function testOnWebsiteSearchIndexUnsupportedFieldsGroup()
    {
        $websiteId = 1;
        $context = [
            AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main'],
            AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId
        ];
        $event = new IndexEntityEvent(\stdClass::class, [], $context);

        $this->websiteContextManager->expects($this->never())
            ->method($this->anything());

        $this->visibilityIndexer->expects($this->never())
            ->method($this->anything());

        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndex(array $context)
    {
        $websiteId = 1;
        $context[AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY] = $websiteId;
        $event = new IndexEntityEvent(\stdClass::class, [], $context);

        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $this->visibilityIndexer->expects($this->once())
            ->method('addIndexInfo')
            ->with($event, $websiteId);

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function validContextDataProvider(): array
    {
        return [
            [[]],
            [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['visibility']]]
        ];
    }

    /**
     * No exception (it may break queue) listener will return safely
     */
    public function testOnWebsiteSearchIndexWhenWebsiteIdIsNotInContext()
    {
        $event = new IndexEntityEvent(\stdClass::class, [], []);

        $this->visibilityIndexer->expects($this->never())
            ->method('addIndexInfo');

        $this->listener->onWebsiteSearchIndex($event);
    }
}
