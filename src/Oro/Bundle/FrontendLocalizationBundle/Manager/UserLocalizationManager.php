<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Represents the entry point for the localization settings of the store frontend.
 */
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

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param ConfigManager $configManager
     * @param WebsiteManager $websiteManager
     * @param BaseUserManager $userManager
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        ConfigManager $configManager,
        WebsiteManager $websiteManager,
        BaseUserManager $userManager,
        LocalizationManager $localizationManager
    ) {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->configManager = $configManager;
        $this->websiteManager = $websiteManager;
        $this->userManager = $userManager;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @return Localization[]
     */
    public function getEnabledLocalizations()
    {
        $ids = array_map(function ($id) {
            return (int)$id;
        }, (array)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)));

        return $this->localizationManager->getLocalizations($ids);
    }

    /**
     * @return Localization|null
     */
    public function getDefaultLocalization()
    {
        $localization = $this->localizationManager->getLocalization(
            (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
        );

        return $localization ?: $this->localizationManager->getDefaultLocalization();
    }

    /**
     * @param Website|null $website
     * @return Localization|null
     */
    public function getCurrentLocalization(Website $website = null)
    {
        $website = $this->getWebsite($website);

        if (!$website) {
            return null;
        }

        $localization = null;

        $user = $this->getLoggedUser();
        if ($user instanceof CustomerUser) {
            $userSettings = $user->getWebsiteSettings($website);
            if ($userSettings) {
                $localization = $userSettings->getLocalization();
            }
        } elseif ($this->session->isStarted()) {
            $sessionStoredLocalizations = $this->getSessionLocalizations();
            if (array_key_exists($website->getId(), $sessionStoredLocalizations)) {
                $localization = $this->localizationManager->getLocalization(
                    $sessionStoredLocalizations[$website->getId()]
                );
            }
        }

        if (!$localization || !array_key_exists($localization->getId(), $this->getEnabledLocalizations())) {
            $localization = $this->getDefaultLocalization();
        }

        return $localization;
    }

    /**
     * @param CustomerUser $customerUser
     * @param Website|null $website
     * @return null|Localization
     */
    public function getCurrentLocalizationByCustomerUser(
        CustomerUser $customerUser,
        Website $website = null
    ): ?Localization {
        $website = $this->getWebsite($website);

        if (!$website) {
            return null;
        }

        $localization = null;
        $customerUserSettings = $customerUser->getWebsiteSettings($website);
        if ($customerUserSettings) {
            $localization = $customerUserSettings->getLocalization();
        }

        if (!$localization || !array_key_exists($localization->getId(), $this->getEnabledLocalizations())) {
            $localization = $this->getWebsiteDefaultLocalization($website);
        }

        return $localization;
    }

    /**
     * @param Localization $localization
     * @param Website|null $website
     */
    public function setCurrentLocalization(Localization $localization, Website $website = null)
    {
        $website = $this->getWebsite($website);
        if (!$website) {
            return;
        }

        $user = $this->getLoggedUser();
        if ($user instanceof CustomerUser) {
            $userWebsiteSettings = $user->getWebsiteSettings($website);
            if (!$userWebsiteSettings) {
                $userWebsiteSettings = new CustomerUserSettings($website);
                $user->setWebsiteSettings($userWebsiteSettings);
            }
            $userWebsiteSettings->setLocalization($localization);
            $this->userManager->getStorageManager()->flush();
        } elseif ($this->session->isStarted()) {
            $sessionLocalizations = $this->getSessionLocalizations();
            $sessionLocalizations[$website->getId()] = $localization->getId();
            $this->session->set(self::SESSION_LOCALIZATIONS, $sessionLocalizations);
        }
    }

    /**
     * @return null|CustomerUser
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

    /**
     * @param Website $website
     * @return Localization|null
     */
    private function getWebsiteDefaultLocalization(Website $website): ?Localization
    {
        $localization = $this->localizationManager->getLocalization(
            (int)$this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                false,
                false,
                $website
            )
        );

        return $localization ?: $this->localizationManager->getDefaultLocalization();
    }
}
