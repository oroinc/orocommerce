<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Layout\DataProvider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;

class FrontendLocalizationProvider
{
    /**
     * @var UserLocalizationManager
     */
    protected $userLocalizationManager;

    /**
     * @param UserLocalizationManager $userLocalizationManager
     */
    public function __construct(UserLocalizationManager $userLocalizationManager)
    {
        $this->userLocalizationManager = $userLocalizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledLocalizations()
    {
        return $this->userLocalizationManager->getEnabledLocalizations();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        return $this->userLocalizationManager->getCurrentLocalization();
    }

    /**
     * @return string
     */
    public function getCurrentLanguageCode()
    {
        $localization = $this->getCurrentLocalization();
        if (!$localization) {
            $localization = $this->userLocalizationManager->getDefaultLocalization();
        }

        return $this->convertLanguageCode($localization->getLanguageCode());
    }

    /**
     * Converts language code to comply with [RFC1766](http://www.ietf.org/rfc/rfc1766.txt)
     *
     * @param string $languageCode
     *
     * @return string
     */
    private function convertLanguageCode(string $languageCode)
    {
        return str_replace('_', '-', $languageCode);
    }
}
