<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\DependencyInjection\Configuration;

/**
 * Get Address Resolver Granularity settings.
 */
class AddressResolverSettingsProvider
{
    public const ADDRESS_RESOLVER_GRANULARITY_COUNTRY = 'country';
    public const ADDRESS_RESOLVER_GRANULARITY_REGION = 'region';
    public const ADDRESS_RESOLVER_GRANULARITY_ZIP = 'zip_code';
    public const ADDRESS_RESOLVER_GRANULARITY_COUNTRY_ZIP = 'country_and_zip_code';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    public function getAddressResolverGranularity()
    {
        $key = Configuration::ROOT_NODE
            . ConfigManager::SECTION_MODEL_SEPARATOR
            . Configuration::ADDRESS_RESOLVER_GRANULARITY;

        return (string)$this->configManager->get($key);
    }
}
