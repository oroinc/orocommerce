<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\FallbackBundle\DependencyInjection\OroB2BFallbackExtension;

class OroB2BFallbackExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BFallbackExtension());

        $expectedDefinitions = [
            'orob2b_fallback.form.type.website_collection',
            'orob2b_fallback.form.type.website_property',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
