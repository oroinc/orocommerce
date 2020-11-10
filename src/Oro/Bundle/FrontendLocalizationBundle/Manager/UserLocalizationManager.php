<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
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

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ConfigManager */
    protected $configManager;

    /** @var WebsiteManager */
    protected $websiteManager;

    /** @var LocalizationManager */
    protected $localizationManager;

    /** @var array */
    private $currentLocalizations = [];

    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param ManagerRegistry $doctrine
     * @param ConfigManager $configManager
     * @param WebsiteManager $websiteManager
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        WebsiteManager $websiteManager,
        LocalizationManager $localizationManager
    ) {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->websiteManager = $websiteManager;
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

        $user = $this->getLoggedUser();

        $websiteId = $website->getId();
        $userId = $user instanceof CustomerUser ? $user->getId() : 0;
        if (isset($this->currentLocalizations[$websiteId][$userId])) {
            return $this->currentLocalizations[$websiteId][$userId];
        }

        $localization = null;
        $enabledLocalizations = $this->getEnabledLocalizations();
        if ($userId !== 0) {
            $userSettings = $user->getWebsiteSettings($website);
            if ($userSettings) {
                $localization = $userSettings->getLocalization();
            }
        } elseif ($this->session->isStarted()) {
            $localization = $enabledLocalizations[$this->getSessionLocalizationIdByWebsiteId($websiteId)] ?? null;
        }

        if (!$localization || !isset($enabledLocalizations[$localization->getId()])) {
            $localization = $this->getDefaultLocalization();
        }

        $this->currentLocalizations[$websiteId][$userId] = $localization;

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
     * @param bool $forceSessionStart Sets localization to the session even if it was not started.
     *  Enabled for ajax action but not for API to remain it stateless
     */
    public function setCurrentLocalization(
        Localization $localization,
        Website $website = null,
        $forceSessionStart = false
    ) {
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
            $this->doctrine->getManagerForClass(CustomerUser::class)->flush();
        } elseif ($this->session->isStarted() || $forceSessionStart) {
            $sessionLocalizations = $this->getSessionLocalizations();
            $sessionLocalizations[$website->getId()] = $localization->getId();
            $this->session->set(self::SESSION_LOCALIZATIONS, $sessionLocalizations);
        }
    }

    /**
     * @return mixed
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
     * @param int $websiteId
     *
     * @return int
     */
    private function getSessionLocalizationIdByWebsiteId(int $websiteId): int
    {
        return $this->getSessionLocalizations()[$websiteId] ?? 0;
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
