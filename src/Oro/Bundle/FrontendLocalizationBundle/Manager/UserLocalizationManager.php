<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Represents the entry point for the localization settings of the storefront.
 */
class UserLocalizationManager implements UserLocalizationManagerInterface
{
    public const SESSION_LOCALIZATIONS = 'localizations_by_website';

    /** @var array */
    private $currentLocalizations = [];

    public function __construct(
        protected RequestStack $requestStack,
        protected TokenStorageInterface $tokenStorage,
        protected ManagerRegistry $doctrine,
        protected ConfigManager $configManager,
        protected WebsiteManager $websiteManager,
        protected LocalizationManager $localizationManager,
        private ApiRequestHelper $apiRequestHelper
    ) {
    }

    #[\Override]
    public function getEnabledLocalizations(): array
    {
        $ids = array_map(function ($id) {
            return (int)$id;
        }, (array)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)));

        return $this->localizationManager->getLocalizations($ids);
    }

    #[\Override]
    public function getDefaultLocalization(): ?Localization
    {
        $localization = $this->localizationManager->getLocalization(
            (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
        );

        return $localization ?: $this->localizationManager->getDefaultLocalization();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function getCurrentLocalization(?Website $website = null): ?Localization
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
        $request = $this->requestStack->getCurrentRequest();
        if ($userId !== 0) {
            $userSettings = $user->getWebsiteSettings($website);
            if ($userSettings) {
                $localization = $userSettings->getLocalization();
            }
        } elseif (null !== $request && $request->hasSession() && !$this->isApiRequest($request)) {
            $localization = $enabledLocalizations[$this->getSessionLocalizationIdByWebsiteId($websiteId)] ?? null;
        }

        if (!$localization || !isset($enabledLocalizations[$localization->getId()])) {
            $localization = $this->getDefaultLocalization();
        }

        $this->currentLocalizations[$websiteId][$userId] = $localization;

        return $localization;
    }

    #[\Override]
    public function getCurrentLocalizationByCustomerUser(
        CustomerUser $customerUser,
        ?Website $website = null
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

    #[\Override]
    public function setCurrentLocalization(Localization $localization, ?Website $website = null): void
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
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request && $request->hasSession()) {
                $request->getSession()->set(self::SESSION_LOCALIZATIONS, $sessionLocalizations);
                $request->setLocale($localization->getLanguageCode());
            }
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
    protected function getWebsite(?Website $website = null)
    {
        return $website ?: $this->websiteManager->getCurrentWebsite();
    }

    /**
     * @return array
     */
    protected function getSessionLocalizations()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return [];
        }

        return (array)$request->getSession()->get(self::SESSION_LOCALIZATIONS);
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

    private function isApiRequest(?Request $request): bool
    {
        $pathInfo = $request?->getPathInfo() ?? '';

        return
            $this->apiRequestHelper->isApiRequest($pathInfo)
            || str_starts_with($request?->attributes?->get('_route') ?? '', 'oro_oauth2_server')
            || str_contains($pathInfo, '/oauth2-token');
    }
}
