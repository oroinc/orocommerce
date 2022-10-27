<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroRFPExtensionTest extends ExtensionTestCase
{
    public function testExtension()
    {
        $extension = new OroRFPExtension();

        $this->loadExtension($extension);

        $expectedDefinitions = [
            'oro_rfp.form.type.extension.frontend_request_data_storage',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded(['oro_rfp']);
    }
}
