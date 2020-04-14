<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\UPSBundle\DependencyInjection\OroUPSExtension;

class OroUPSExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroUPSExtension
     */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new OroUPSExtension();
    }

    protected function tearDown(): void
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
            'oro_ups.validator.remove_used_shipping_service',
            'oro_ups.entity_listener.channel',
            'oro_ups.entity_listener.transport',
            'oro_ups.shipping_units_mapper',
            'oro_ups.disable_integration_listener',
            'oro_ups.client.url_provider_basic',
            'oro_ups.client.factory_basic',
            'oro_ups.connection.validator.request.factory.rate_request',
            'oro_ups.connection.validator.result.factory',
            'oro_ups.connection.validator',
            'oro_ups.handler.action.invalidate_cache',
            'oro_ups.repository.shipping_service',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
