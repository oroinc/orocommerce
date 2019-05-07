<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;
use Oro\Bundle\WebsiteSearchBundle\EventListener\EntityConfigListener;

class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testClearMappingCache()
    {
        /** @var MappingConfigurationCacheProvider $mappingConfigurationCacheProvider */
        $mappingConfigurationCacheProvider = $this->createMock(MappingConfigurationCacheProvider::class);
        $mappingConfigurationCacheProvider->expects(self::once())
            ->method('deleteConfiguration');

        $listener = new EntityConfigListener($mappingConfigurationCacheProvider);
        $listener->clearMappingCache();
    }
}
