<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\RFPAdminBundle\DependencyInjection\OroB2BRFPAdminExtension;

class OroB2BRFPAdminExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroB2BRFPAdminExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_rfp.request.class',
            'orob2b_rfp.request.status.class',
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_rfp_admin', $extension->getAlias());
    }
}
