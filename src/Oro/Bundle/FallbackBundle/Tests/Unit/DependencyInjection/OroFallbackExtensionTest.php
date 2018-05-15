<?php

namespace Oro\Bundle\FallbackBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FallbackBundle\DependencyInjection\OroFallbackExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFallbackExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroFallbackExtension());

        $expectedDefinitions = [
            'oro_fallback.form.type.website_collection',
            'oro_fallback.form.type.website_property',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
