<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class OroWebsiteSearchExtensionTest extends ExtensionTestCase
{
    /** @var OroWebsiteSearchExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroWebsiteSearchExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedParameters = [
            'oro_website_search.engine'
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias()
    {
        $alias = $this->extension->getAlias();
        $this->assertEquals('oro_website_search', $alias);
    }
}
