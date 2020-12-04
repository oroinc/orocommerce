<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\EventListener\EntityConfigListener;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testClearMappingCache()
    {
        /** @var WebsiteSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject $mappingConfigurationProvider */
        $searchMappingProvider = $this->createMock(WebsiteSearchMappingProvider::class);
        $searchMappingProvider->expects(self::once())
            ->method('clearCache');

        $listener = new EntityConfigListener($searchMappingProvider);
        $listener->clearMappingCache();
    }
}
