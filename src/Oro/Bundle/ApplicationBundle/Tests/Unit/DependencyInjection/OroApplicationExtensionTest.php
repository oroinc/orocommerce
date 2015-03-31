<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ApplicationBundle\DependencyInjection\OroApplicationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroApplicationExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroApplicationExtension());

        $expectedParameters = [
            'oro_application.twig.application_url_extension.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_application.twig.application_url_extension',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
