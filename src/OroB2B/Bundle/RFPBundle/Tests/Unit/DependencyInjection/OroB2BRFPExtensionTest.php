<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\RFPBundle\DependencyInjection\OroB2BRFPExtension;

class OroB2BRFPExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BRFPExtension());

        $expectedParameters = [
            'orob2b_rfp.request.class',
            'orob2b_rfp.request.status.class',
        ];
        $this->assertParametersLoaded($expectedParameters);
    }
}
