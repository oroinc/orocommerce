<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\EventListener\EntityConfigListener;

class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testClearMappingCache()
    {
        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects(self::once())
            ->method('clearCache');

        $listener = new EntityConfigListener($searchMappingProvider);
        $listener->clearMappingCache();
    }
}
