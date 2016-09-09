<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;

class OroRFPExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroRFPExtension();

        $this->loadExtension($extension);

        $expectedDefinitions = [
            'oro_rfp.form.type.extension.frontend_request_data_storage',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedParameters = [
            'oro_rfp.entity.request.class',
            'oro_rfp.entity.request.status.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_rfp', $extension->getAlias());

        $this->assertExtensionConfigsLoaded([OroRFPExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroRFPExtension();
        $this->assertEquals(OroRFPExtension::ALIAS, $extension->getAlias());
    }
}
