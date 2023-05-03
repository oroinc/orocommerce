<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteQueryEvent;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class ProcessAutocompleteQueryEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $engine = $this->createMock(EngineInterface::class);

        $query = new WebsiteSearchQuery($engine, new Query());
        $query->setFrom('first');

        $event = new ProcessAutocompleteQueryEvent($query, 'request');

        $this->assertEquals($query, $event->getQuery());
        $this->assertEquals('request', $event->getQueryString());

        $newQuery = new WebsiteSearchQuery($engine, new Query());
        $newQuery->setFrom('second');

        $event->setQuery($newQuery);
        $this->assertEquals($newQuery, $event->getQuery());
    }
}
