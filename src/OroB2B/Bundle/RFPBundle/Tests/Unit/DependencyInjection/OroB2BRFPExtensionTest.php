<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\RFPBundle\DependencyInjection\OroB2BRFPExtension;

class OroB2BRFPExtensionTest extends ExtensionTestCase
{
    /**
     * Test Extension
     */
    public function testExtension()
    {
        $extension = new OroB2BRFPExtension();

        $this->loadExtension($extension);

        $expectedParameters = [
            'orob2b_rfp.entity.request.class',
            'orob2b_rfp.entity.request.status.class',
        ];

        $this->assertParametersLoaded($expectedParameters);

        $this->assertEquals('oro_b2b_rfp', $extension->getAlias());
    }
}
