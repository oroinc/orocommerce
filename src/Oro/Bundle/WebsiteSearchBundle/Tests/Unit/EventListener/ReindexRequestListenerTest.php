<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexRequestListener;

class ReindexRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASSNAME = 'testClass';
    private const TEST_WEBSITE_ID = 1234;

    /** @var ReindexRequestListener */
    private $listener;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $regularIndexer;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $asyncIndexer;

    /** @var ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject */
    private $granularizer;

    protected function setUp(): void
    {
        $this->regularIndexer = $this->createMock(IndexerInterface::class);
        $this->asyncIndexer = $this->createMock(IndexerInterface::class);
        $this->granularizer = $this->createMock(ReindexMessageGranularizer::class);

        $this->listener = new ReindexRequestListener(
            $this->regularIndexer,
            $this->asyncIndexer
        );
        $this->listener->setReindexMessageGranularizer($this->granularizer);
    }

    public function testProcessWithoutIndexers()
    {
        $event = new ReindexationRequestEvent(
            [self::TEST_CLASSNAME],
            [self::TEST_WEBSITE_ID],
            [],
            true
        );

        $this->granularizer->expects($this->never())
            ->method('process');

        $this->regularIndexer->expects($this->never())
            ->method('reindex');

        $this->asyncIndexer->expects($this->never())
            ->method('reindex');

        (new ReindexRequestListener())->process($event);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $classes, array $websiteIds, array $productIds)
    {
        $event = new ReindexationRequestEvent($classes, $websiteIds, $productIds, false);

        if ($productIds) {
            $this->granularizer->expects($this->once())
                ->method('process')
                ->with($classes, $websiteIds, [
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                    AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
                ])
                ->willReturn([
                    [
                        'class' => $classes,
                        'context' => [
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
                        ],
                    ]
                ]);
        }

        $this->regularIndexer->expects($this->once())
            ->method('reindex')
            ->with($classes, [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
            ]);

        $this->asyncIndexer->expects($this->never())
            ->method('reindex');

        $this->listener->process($event);
    }

    public function testProcessWithDisabledListener()
    {
        $this->granularizer->expects($this->never())
            ->method('process');

        $this->regularIndexer->expects($this->never())
            ->method('reindex');

        $this->asyncIndexer->expects($this->never())
            ->method('reindex');

        $this->disableListener();
        $this->listener->process(new ReindexationRequestEvent());
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcessAsync(array $classes, array $websiteIds, array $productIds)
    {
        $event = new ReindexationRequestEvent($classes, $websiteIds, $productIds, true);

        if ($productIds) {
            $this->granularizer->expects($this->once())
                ->method('process')
                ->with($classes, $websiteIds, [
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                    AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
                ])
                ->willReturn([
                    [
                        'class' => $classes,
                        'context' => [
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
                        ],
                    ]
                ]);
        }

        $this->asyncIndexer->expects($this->once())
            ->method('reindex')
            ->with($classes, [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
            ]);

        $this->regularIndexer->expects($this->never())
            ->method('reindex');

        $this->listener->process($event);
    }

    private function disableListener()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->listener);
        $this->listener->setEnabled(false);
    }

    public function processDataProvider(): array
    {
        return [
            'with products and websites' => [
                'classes' => [self::TEST_CLASSNAME],
                'websiteIds' => [self::TEST_WEBSITE_ID],
                'productIds' => [1, 3, 7],
            ],
            'without websites' => [
                'classes' => [self::TEST_CLASSNAME],
                'websiteIds' => [],
                'productIds' => [1, 3, 7],
            ],
            'without products' => [
                'classes' => [self::TEST_CLASSNAME],
                'websiteIds' => [self::TEST_WEBSITE_ID],
                'productIds' => [],
            ],
            'without websites and products' => [
                'classes' => [self::TEST_CLASSNAME],
                'websiteIds' => [],
                'productIds' => [],
            ],
        ];
    }
}
