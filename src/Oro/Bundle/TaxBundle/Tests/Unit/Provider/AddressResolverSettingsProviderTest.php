<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

class AddressResolverSettingsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AddressResolverSettingsProvider */
    protected $provider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AddressResolverSettingsProvider($this->config);
    }

    public function testFromConfig()
    {
        $this->config->expects($this->once())->method('get')
            ->with('oro_tax.address_resolver_granularity')
            ->willReturn(AddressResolverSettingsProvider::ADDRESS_RESOLVER_GRANULARITY_COUNTRY);

        $this->assertEquals(
            AddressResolverSettingsProvider::ADDRESS_RESOLVER_GRANULARITY_COUNTRY,
            $this->provider->getAddressResolverGranularity()
        );
    }
}
