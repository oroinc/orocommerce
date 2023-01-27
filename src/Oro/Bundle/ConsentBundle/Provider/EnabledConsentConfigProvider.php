<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;

/**
 * Provides consents enabled in a website config.
 */
class EnabledConsentConfigProvider implements EnabledConsentConfigProviderInterface
{
    private ConfigManager $configManager;
    private ConsentConfigConverter $converter;
    private ConsentContextProviderInterface $contextProvider;

    public function __construct(
        ConfigManager $configManager,
        ConsentConfigConverter $converter,
        ConsentContextProviderInterface $contextProvider
    ) {
        $this->configManager = $configManager;
        $this->converter = $converter;
        $this->contextProvider = $contextProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsentConfigs(): array
    {
        $website = $this->contextProvider->getWebsite();
        if (null === $website) {
            // return empty result if a website cannot be resolved
            return [];
        }

        return $this->converter->convertFromSaved(
            (array)$this->configManager->get(
                Configuration::getConfigKey(Configuration::ENABLED_CONSENTS),
                false,
                false,
                $website
            )
        );
    }
}
