<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class OroWebsiteSearchExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroWebsiteSearchExtension());

        $expectedParameters = [
            'oro_website_search.engine'
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
