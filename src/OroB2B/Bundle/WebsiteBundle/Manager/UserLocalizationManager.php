<?php

namespace OroB2B\Bundle\WebsiteBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class UserLocalizationManager
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LocalizationProvider
     */
    protected $localizationProvider;

    /**
     * @param ConfigManager $configManager
     * @param LocalizationProvider $localizationProvider
     */
    public function __construct(
        ConfigManager $configManager,
        LocalizationProvider $localizationProvider
    ) {
        $this->configManager = $configManager;
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * @return Localization[]
     */
    public function getEnabledLocalizations()
    {
        return $this->localizationProvider->getLocalizations(
            (array)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
        );
    }

    /**
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        // TODO: must be fixed in scope https://magecore.atlassian.net/browse/BB-3747
        $localizations = $this->getEnabledLocalizations();

        return reset($localizations);
    }
}
