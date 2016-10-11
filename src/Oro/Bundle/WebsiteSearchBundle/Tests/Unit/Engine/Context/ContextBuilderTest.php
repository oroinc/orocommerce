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
            'for website only' => [
                new ReindexationRequestEvent(null, 1),
                [
                    AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 1,
                ],
            ],
            'empty'     => [
                new ReindexationRequestEvent(null, null),
                [],
            ],
        ];
    }
}
