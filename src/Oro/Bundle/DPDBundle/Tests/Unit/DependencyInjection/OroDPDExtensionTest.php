<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DPDBundle\DependencyInjection\OroDPDExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroDPDExtensionTest extends ExtensionTestCase
{
    /** @var OroDPDExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroDPDExtension();
    }

    protected function tearDown()
    {
        unset($this->extension);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_dpd.provider.channel',
            'oro_dpd.provider.transport',
            'oro_dpd.handler.order_shipping_dpd',
            'oro_dpd.entity_listener.channel',
            'oro_dpd.entity_listener.transport',
            'oro_dpd.event_listener.shipping_method_config_data',
            'oro_dpd.validator.remove_used_shipping_service',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
