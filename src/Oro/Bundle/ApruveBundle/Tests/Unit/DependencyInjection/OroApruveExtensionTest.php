<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ApruveBundle\DependencyInjection\OroApruveExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroApruveExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroApruveExtension());

        $expectedDefinitions = [];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias()
    {
        $extension = new OroApruveExtension();
        static::assertEquals('oro_apruve', $extension->getAlias());
    }
}
