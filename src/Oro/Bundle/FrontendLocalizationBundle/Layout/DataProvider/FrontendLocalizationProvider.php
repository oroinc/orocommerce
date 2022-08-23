<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;

/**
 * Layout dataprovider for getting localizations and language codes.
 */
class FrontendLocalizationProvider
{
    protected LocalizationProviderInterface $localizationProvider;

    protected UserLocalizationManagerInterface $localizationManager;

    public function __construct(
        LocalizationProviderInterface $localizationProvider,
        UserLocalizationManagerInterface $localizationManager
    ) {
        $this->localizationProvider = $localizationProvider;
        $this->localizationManager = $localizationManager;
    }

    public function getEnabledLocalizations(): array
    {
        return $this->localizationManager->getEnabledLocalizations();
    }

    public function getCurrentLocalization(): ?Localization
    {
        return $this->localizationProvider->getCurrentLocalization();
    }

    public function getCurrentLanguageCode(): string
    {
        $localization = $this->getCurrentLocalization();
        if (!$localization) {
            $localization = $this->localizationManager->getDefaultLocalization();
        }

        return $this->convertLanguageCode($localization->getLanguageCode());
    }

    /**
     * Converts language code to comply with [RFC1766](http://www.ietf.org/rfc/rfc1766.txt)
     */
    private function convertLanguageCode(string $languageCode): string
    {
        return str_replace('_', '-', $languageCode);
    }
}
