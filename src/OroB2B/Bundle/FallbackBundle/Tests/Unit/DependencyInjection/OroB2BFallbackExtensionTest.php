<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use OroB2B\Bundle\FallbackBundle\DependencyInjection\OroB2BFallbackExtension;

class OroB2BFallbackExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BFallbackExtension());

        $expectedParameters = [
            'orob2b_fallback.form.type.fallback_property.class',
            'orob2b_fallback.form.type.fallback_value.class',

        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_fallback.form.type.fallback_property',
            'orob2b_fallback.form.type.localized_property',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
