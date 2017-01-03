<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FlatRateBundle\DependencyInjection\OroFlatRateExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFlatRateExtensionTest extends ExtensionTestCase
{
    /** @var OroFlatRateExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new OroFlatRateExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAliasReturnsString()
    {
        $this->assertTrue(is_string($this->extension->getAlias()));
    }
}
