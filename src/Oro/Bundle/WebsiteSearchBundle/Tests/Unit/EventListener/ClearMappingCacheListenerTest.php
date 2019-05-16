<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ClearMappingCacheListener;

class ClearMappingCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnAfterDatabasePreparation()
    {
        /** @var MappingConfigurationCacheProvider|\PHPUnit_Framework_MockObject_MockObject $mappingCacheProvider */
        $mappingCacheProvider = $this->createMock(MappingConfigurationCacheProvider::class);
        $listener = new ClearMappingCacheListener($mappingCacheProvider);

        $mappingCacheProvider->expects(self::once())
            ->method('deleteConfiguration');

        $listener->onAfterDatabasePreparation();
    }
}
