<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextBuilder;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ContextBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderCreateForReindexation
     * @param ReindexationRequestEvent $event
     * @param array                    $contextExpected
     */
    public function testCreateForReindexation(ReindexationRequestEvent $event, array $contextExpected)
    {
        $this->assertEquals($contextExpected, ContextBuilder::createForReindexation($event));
    }

    /**
     * @return array
     */
    public function dataProviderCreateForReindexation()
    {
        return [
            'for websites only'   => [
                new ReindexationRequestEvent([], [1, 2]),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_ID_KEY   => [1, 2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [],
                ],
            ],
            'for ids only'        => [
                new ReindexationRequestEvent([], [], [3, 4]),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_ID_KEY   => [],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3, 4],
                ],
            ],
            'for websites and ids' => [
                new ReindexationRequestEvent([], [1, 2], [3, 4]),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_ID_KEY   => [1, 2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3, 4],
                ],
            ],
            'empty'               => [
                new ReindexationRequestEvent(),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_ID_KEY   => [],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [],
                ],
            ],
        ];
    }
}
