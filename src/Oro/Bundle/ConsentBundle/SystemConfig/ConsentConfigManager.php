<?php

namespace Oro\Bundle\ConsentBundle\SystemConfig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * This manager helps to update consent configs
 */
class ConsentConfigManager
{
    /**
     * @var ConsentConfigConverter
     */
    private $converter;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ConfigManager
     */
    private $globalConfig;

    /**
     * @param ConfigManager $configManager
     * @param ConfigManager $globalConfig
     * @param ConsentConfigConverter $converter
     */
    public function __construct(
        ConfigManager $configManager,
        ConfigManager $globalConfig,
        ConsentConfigConverter $converter
    ) {
        $this->configManager = $configManager;
        $this->globalConfig = $globalConfig;
        $this->converter = $converter;
    }

    /**
     * @param Consent $consent
     * @param Website|null $website
     */
    public function updateConsentsConfigForWebsiteScope(Consent $consent, Website $website = null)
    {
        $this->updateConsentsConfig($consent, $this->configManager, $website);
    }

    /**
     * @param Consent $consent
     */
    public function updateConsentsConfigForGlobalScope(Consent $consent)
    {
        $this->updateConsentsConfig($consent, $this->globalConfig);
    }

    /**
     * @param Consent $consent
     * @param ConfigManager|null $configManager
     * @param Website|null $website
     */
    private function updateConsentsConfig(
        Consent $consent,
        ConfigManager $configManager = null,
        Website $website = null
    ) {
        $configKey = Configuration::getConfigKey(Configuration::ENABLED_CONSENTS);
        $config = $configManager->get($configKey, false, true, $website);

        if (empty($config[ConfigManager::VALUE_KEY])
            || $config[ConfigManager::USE_PARENT_SCOPE_VALUE_KEY] === true
        ) {
            return;
        }

        $configList = $this->converter->convertFromSaved($config[ConfigManager::VALUE_KEY]);
        $newConfigList = array_filter(
            $configList,
            function (ConsentConfig $consentConfig) use ($consent) {
                return $consentConfig->getConsent()->getId() !== $consent->getId();
            }
        );

        if (count($newConfigList) < count($configList)) {
            $configManager->set(
                $configKey,
                $this->converter->convertBeforeSave($newConfigList),
                $website
            );
            $configManager->flush();
        }
    }
}
