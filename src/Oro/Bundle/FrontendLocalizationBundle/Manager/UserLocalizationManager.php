<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
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
class UserLocalizationManager implements UserLocalizationManagerInterface
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
     * {@inheritDoc}
     */
    public function getEnabledLocalizations(): array
    {
        $ids = array_map(function ($id) {
            return (int)$id;
        }, (array)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)));

        return $this->localizationManager->getLocalizations($ids);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultLocalization(): ?Localization
    {
        $localization = $this->localizationManager->getLocalization(
            (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
        );

        return $localization ?: $this->localizationManager->getDefaultLocalization();
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentLocalization(Website $website = null): ?Localization
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
     * {@inheritDoc}
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
     * {@inheritdoc}
     */
    public function setCurrentLocalization(Localization $localization, Website $website = null): void
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
            $this->doctrine->getManagerForClass(CustomerUser::class)->flush();
        } else {
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

    private function getSessionLocalizationIdByWebsiteId(int $websiteId): int
    {
        return $this->getSessionLocalizations()[$websiteId] ?? 0;
    }

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
