<?php

namespace Oro\Bundle\ProductBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides a method to update current state of the product filters sidebar.
 */
class UserProductFiltersSidebarStateManager
{
    private const PRODUCT_FILTERS_SIDEBAR_STATES = 'product_filters_sidebar_states_by_website';

    private RequestStack $requestStack;

    private ManagerRegistry $managerRegistry;

    private TokenAccessorInterface $tokenAccessor;

    private WebsiteManager $websiteManager;

    private ConfigManager $configManager;

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        TokenAccessorInterface $tokenAccessor,
        WebsiteManager $websiteManager,
        ConfigManager $configManager
    ) {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
        $this->tokenAccessor = $tokenAccessor;
        $this->websiteManager = $websiteManager;
        $this->configManager = $configManager;
    }

    public function setCurrentProductFiltersSidebarState(bool $sidebarExpanded, Website $website = null): void
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
            $userWebsiteSettings->setProductFiltersSidebarExpanded($sidebarExpanded);
            $this->managerRegistry->getManagerForClass(CustomerUser::class)->flush();
        } else {
            $productFiltersSidebarExpandedStates = $this->getProductFiltersSidebarExpandedStates();
            $productFiltersSidebarExpandedStates[$website->getId()] = $sidebarExpanded;
            $this->requestStack->getSession()->set(
                self::PRODUCT_FILTERS_SIDEBAR_STATES,
                $productFiltersSidebarExpandedStates
            );
        }
    }

    public function isProductFiltersSidebarExpanded(Website $website = null): bool
    {
        $isSidebarExpanded = null;
        $website = $this->getWebsite($website);
        if ($website) {
            $user = $this->getLoggedUser();
            if ($user instanceof CustomerUser) {
                $userSettings      = $user->getWebsiteSettings($website);
                $isSidebarExpanded = $userSettings?->isProductFiltersSidebarExpanded();
            } else {
                $currentRequest = $this->requestStack->getCurrentRequest();
                if ($currentRequest && $currentRequest->hasSession()) {
                    $isSidebarExpanded = $this->isSessionProductFiltersSidebarExpanded($website->getId());
                }
            }
        }

        if (null === $isSidebarExpanded) {
            $isSidebarExpanded = $this->getDefaultFiltersDisplaySettingsState($website)
                === Configuration::FILTERS_DISPLAY_SETTINGS_STATE_EXPANDED;
        }

        return $isSidebarExpanded;
    }

    private function getProductFiltersSidebarExpandedStates(): array
    {
        return (array)$this->requestStack->getSession()
            ->get(self::PRODUCT_FILTERS_SIDEBAR_STATES, []);
    }

    private function isSessionProductFiltersSidebarExpanded(int $websiteId): ?bool
    {
        return $this->getProductFiltersSidebarExpandedStates()[$websiteId] ?? null;
    }

    private function getDefaultFiltersDisplaySettingsState(Website $website = null): string
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::FILTERS_DISPLAY_SETTINGS_STATE),
            false,
            false,
            $website
        );
    }

    private function getLoggedUser(): UserInterface|string|null
    {
        $token = $this->tokenAccessor->getToken();

        return $token?->getUser();
    }

    private function getWebsite(Website $website = null): ?Website
    {
        return $website ?: $this->websiteManager->getCurrentWebsite();
    }
}
