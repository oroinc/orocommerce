<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

class AddressResolverSettingsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var AddressResolverSettingsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigManager::class);

        $this->provider = new AddressResolverSettingsProvider($this->config);
    }

    public function testFromConfig()
    {
        $this->config->expects($this->once())
            ->method('get')
            ->with('oro_tax.address_resolver_granularity')
            ->willReturn(AddressResolverSettingsProvider::ADDRESS_RESOLVER_GRANULARITY_COUNTRY);

        $this->assertEquals(
            AddressResolverSettingsProvider::ADDRESS_RESOLVER_GRANULARITY_COUNTRY,
            $this->provider->getAddressResolverGranularity()
        );
    }
}
