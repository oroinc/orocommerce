<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\EventListener\EntityConfigListener;

class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testClearMappingCache()
    {
        /** @var MappingConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $mappingConfigurationProvider */
        $mappingConfigurationProvider = $this->createMock(MappingConfigurationProvider::class);
        $mappingConfigurationProvider->expects(self::once())
            ->method('clearCache');

        $listener = new EntityConfigListener($mappingConfigurationProvider);
        $listener->clearMappingCache();
    }
}
