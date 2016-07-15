<?php

namespace OroB2B\Bundle\WebsiteBundle\Manager;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserSettings;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class UserLocalizationManager
{
    const SESSION_LOCALIZATIONS = 'localizations_by_website';

    /** @var Session */
    protected $session;

    /** @var ConfigManager */
    protected $configManager;

    /** @var WebsiteManager */
    protected $websiteManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var BaseUserManager */
    protected $userManager;

    /** @var LocalizationProvider */
    protected $localizationProvider;

    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param ConfigManager $configManager
     * @param WebsiteManager $websiteManager
     * @param BaseUserManager $userManager
     * @param LocalizationProvider $localizationProvider
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        ConfigManager $configManager,
        WebsiteManager $websiteManager,
        BaseUserManager $userManager,
        LocalizationProvider $localizationProvider
    ) {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->configManager = $configManager;
        $this->websiteManager = $websiteManager;
        $this->userManager = $userManager;
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
     * @return Localization|null
     */
    public function getDefaultLocalization()
    {
        return  $this->localizationProvider->getLocalization(
            $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
        );
    }

    /**
     * @param Website|null $website
     * @return Localization
     */
    public function getCurrentLocalization(Website $website = null)
    {
        $website = $this->getWebsite($website);

        if (!$website) {
            return null;
        }

        $localization = null;

        $user = $this->getLoggedUser();
        if ($user instanceof AccountUser) {
            $userSettings = $user->getWebsiteSettings($website);
            if ($userSettings) {
                $localization = $userSettings->getLocalization();
            }
        } else {
            $sessionStoredLocalizations = $this->getSessionLocalizations();
            if (array_key_exists($website->getId(), $sessionStoredLocalizations)) {
                $localization = $this->localizationProvider->getLocalization(
                    $sessionStoredLocalizations[$website->getId()]
                );
            }
        }

        $allowedLocalizations = $this->getEnabledLocalizations();
        if (!$localization || !in_array($localization, $allowedLocalizations, true)) {
            $localization = $this->getDefaultLocalization();
        }

        return $localization;
    }

    /**
     * @param int $localizationId
     * @param Website|null $website
     */
    public function setCurrentLocalization($localizationId, Website $website = null)
    {
        $website = $this->getWebsite($website);
        if (!$website) {
            return;
        }

        $user = $this->getLoggedUser();
        if ($user instanceof AccountUser) {
            $userWebsiteSettings = $user->getWebsiteSettings($website);
            if (!$userWebsiteSettings) {
                $userWebsiteSettings = new AccountUserSettings($website);
                $user->setWebsiteSettings($userWebsiteSettings);
            }
            $userWebsiteSettings->setLocalization($this->localizationProvider->getLocalization($localizationId));
            $this->userManager->getStorageManager()->flush();
        } else {
            $sessionLocalizations = $this->getSessionLocalizations();
            $sessionLocalizations[$website->getId()] = $localizationId;
            $this->session->set(self::SESSION_LOCALIZATIONS, $sessionLocalizations);
        }
    }

    /**
     * @return null|AccountUser
     */
    protected function getLoggedUser()
    {
        $token = $this->tokenStorage->getToken();

        return $token ? $token->getUser() : null;
    }

    /**
     * @param Website|null $website
     * @return Website|null
     */
    protected function getWebsite(Website $website = null)
    {
        return $website ?: $this->websiteManager->getCurrentWebsite();
    }

    /**
     * @return array
     */
    protected function getSessionLocalizations()
    {
        return (array)$this->session->get(self::SESSION_LOCALIZATIONS);
    }
}
