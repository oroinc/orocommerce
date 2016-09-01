<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\UPSBundle\DependencyInjection\OroUPSExtension;

class OroUPSExtensionTest extends ExtensionTestCase
{
    /** @var OroUPSExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroUPSExtension();
    }

    protected function tearDown()
    {
        unset($this->extension);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_ups.provider.channel',
            'oro_ups.provider.transport',
            'oro_ups.form.type.transport_setting',
            'oro_ups.form.type.shipping_service',
            'oro_ups.form.type.shipping_service_collection'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
