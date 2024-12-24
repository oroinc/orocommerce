<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextFactory;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use PHPUnit\Framework\TestCase;

final class ContextFactoryTest extends TestCase
{
    /**
     * @dataProvider dataProviderCreateForReindexation
     */
    public function testCreateForReindexation(ReindexationRequestEvent $event, array $contextExpected): void
    {
        $factory = new ContextFactory();
        $this->assertEquals($contextExpected, $factory->createForReindexation($event));
    }

    public function dataProviderCreateForReindexation(): array
    {
        return [
            'for websites only'   => [
                new ReindexationRequestEvent([], [1, 2]),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [1, 2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [],
                ],
            ],
            'for ids only'        => [
                new ReindexationRequestEvent([], [], [3, 4]),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3, 4],
                ],
            ],
            'for batch size only'        => [
                new ReindexationRequestEvent(batchSize: 1000),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [],
                    AbstractIndexer::CONTEXT_BATCH_SIZE => 1000,
                ],
            ],
            'for websites and ids' => [
                new ReindexationRequestEvent([], [1, 2], [3, 4]),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [1, 2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3, 4],
                ],
            ],
            'for websites and ids and field groups' => [
                new ReindexationRequestEvent([], [1, 2], [3, 4], true, ['main']),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [1, 2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3, 4],
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']
                ],
            ],
            'for websites, ids, field groups and batch size' => [
                new ReindexationRequestEvent([], [1, 2], [3, 4], true, ['main'], 1000),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [1, 2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3, 4],
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main'],
                    AbstractIndexer::CONTEXT_BATCH_SIZE => 1000
                ],
            ],
            'empty'               => [
                new ReindexationRequestEvent(),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS   => [],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [],
                ],
            ],
        ];
    }
}
