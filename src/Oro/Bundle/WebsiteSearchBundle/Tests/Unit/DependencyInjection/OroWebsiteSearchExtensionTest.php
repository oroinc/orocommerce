<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class OroWebsiteSearchExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroWebsiteSearchExtension());

        $expectedDefinitions = [
            'oro_website_search.provider.search_engine_config_provider',
            'oro_website_search.engine'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
