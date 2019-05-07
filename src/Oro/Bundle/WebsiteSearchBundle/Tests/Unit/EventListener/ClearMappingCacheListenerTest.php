<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ClearMappingCacheListener;

class ClearMappingCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnAfterDatabasePreparation()
    {
        /** @var MappingConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject $mappingCacheProvider */
        $mappingCacheProvider = $this->createMock(MappingConfigurationProvider::class);
        $listener = new ClearMappingCacheListener($mappingCacheProvider);

        $mappingCacheProvider->expects(self::once())
            ->method('clearCache');

        $listener->onAfterDatabasePreparation();
    }
}
