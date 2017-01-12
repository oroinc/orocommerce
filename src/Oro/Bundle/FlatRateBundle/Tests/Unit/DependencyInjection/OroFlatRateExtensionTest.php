<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FlatRateBundle\DependencyInjection\OroFlatRateExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFlatRateExtensionTest extends ExtensionTestCase
{
    /** @var OroFlatRateExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new OroFlatRateExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_flat_rate.integration.channel',
            'oro_flat_rate.integration.transport',
            'oro_flat_rate.event_listener.shipping_method_config_data',
            'oro_flat_rate.form.type.flat_rate_options',
            'oro_flat_rate.method.provider',
            'oro_flat_rate.builder.flat_rate_method_from_channel',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAliasReturnsString()
    {
        $this->assertTrue(is_string($this->extension->getAlias()));
    }
}
