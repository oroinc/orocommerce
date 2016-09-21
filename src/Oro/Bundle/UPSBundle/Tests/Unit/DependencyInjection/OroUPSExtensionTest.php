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
            'oro_ups.form.type.transport_settings',
            'oro_ups.factory.price_request_factory',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
