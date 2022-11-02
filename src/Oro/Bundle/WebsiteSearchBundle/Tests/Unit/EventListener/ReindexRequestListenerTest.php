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
    const TEST_CLASSNAME = 'testClass';
    const TEST_WEBSITE_ID = 1234;

    /**
     * @var ReindexRequestListener
     */
    protected $listener;

    /**
     * @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $regularIndexerMock;

    /**
     * @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $asyncIndexerMock;

    /**
     * @var ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $granularizer;

    protected function setUp(): void
    {
        $this->regularIndexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $this->asyncIndexerMock   = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $this->granularizer = $this->createMock(ReindexMessageGranularizer::class);

        $this->listener = new ReindexRequestListener(
            $this->regularIndexerMock,
            $this->asyncIndexerMock
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

        $this->granularizer
            ->expects($this->never())
            ->method('process');

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('reindex');

        $this->asyncIndexerMock
            ->expects($this->never())
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
            $this->granularizer
                ->expects($this->once())
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

        $this->regularIndexerMock
            ->expects($this->once())
            ->method('reindex')
            ->with($classes, [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
            ]);

        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('reindex');

        $this->listener->process($event);
    }

    public function testProcessWithDisabledListener()
    {
        $this->granularizer
            ->expects($this->never())
            ->method('process');

        $this->regularIndexerMock->expects($this->never())
            ->method('reindex');

        $this->asyncIndexerMock->expects($this->never())
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
            $this->granularizer
                ->expects($this->once())
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

        $this->asyncIndexerMock
            ->expects($this->once())
            ->method('reindex')
            ->with($classes, [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds,
                AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds
            ]);

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('reindex');

        $this->listener->process($event);
    }

    protected function disableListener()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->listener);
        $this->listener->setEnabled(false);
    }

    /**
     * @return array
     */
    public function processDataProvider()
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
