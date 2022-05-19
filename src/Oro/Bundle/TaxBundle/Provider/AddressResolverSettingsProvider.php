<?php
namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\DependencyInjection\Configuration;

/**
 * Get Address Resolver Granularity settings.
 */
class AddressResolverSettingsProvider
{
    const ADDRESS_RESOLVER_GRANULARITY_COUNTRY = 'country';
    const ADDRESS_RESOLVER_GRANULARITY_REGION = 'region';
    const ADDRESS_RESOLVER_GRANULARITY_ZIP = 'zip_code';
    const ADDRESS_RESOLVER_GRANULARITY_COUNTRY_ZIP = 'country_and_zip_code';

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
