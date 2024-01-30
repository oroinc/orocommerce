<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductPreloadingIndexerListener;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteSearchProductPreloadingIndexerListenerTest extends TestCase
{
    private WebsiteContextManager|MockObject $websiteContextManager;
    private PreloadingManager|MockObject $preloadingManager;
    private WebsiteSearchProductPreloadingIndexerListener $listener;

    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->listener = new WebsiteSearchProductPreloadingIndexerListener(
            $this->websiteContextManager,
            $this->preloadingManager
        );
    }

    public function testSetFieldsToPreload(): void
    {
        $expected = ['product' => [], 'kitItems' => ['collection' => []]];

        $this->listener->setFieldsToPreload($expected);

        self::assertEquals($expected, ReflectionUtil::getPropertyValue($this->listener, 'fieldsToPreload'));
    }

    public function testOnWebsiteSearchIndexDisableListener(): void
    {
        $event = new IndexEntityEvent(Product::class, [], []);

        $this->listener->setEnabled(false);

        $this->websiteContextManager->expects(self::never())
            ->method('getWebsiteId')
            ->with($event->getContext());

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexNotFoundWebsite(): void
    {
        $event = new IndexEntityEvent(Product::class, [], []);

        $this->websiteContextManager->expects(self::once())
            ->method('getWebsiteId')
            ->with($event->getContext())
            ->willReturn(null);

        $this->preloadingManager->expects(self::never())
            ->method('preloadInEntities')
            ->with($event->getEntities(), ReflectionUtil::getPropertyValue($this->listener, 'fieldsToPreload'));

        $this->listener->onWebsiteSearchIndex($event);

        self::assertTrue($event->isPropagationStopped());
    }
}
