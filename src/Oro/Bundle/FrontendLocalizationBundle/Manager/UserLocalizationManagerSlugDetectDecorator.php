<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendLocalizationBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorate UserLocalizationManager in order to use slug localization at first if detected.
 */
class UserLocalizationManagerSlugDetectDecorator implements UserLocalizationManagerInterface
{
    private UserLocalizationManagerInterface $innerManager;

    private RequestStack $requestStack;

    private ManagerRegistry $registry;

    private ConfigManager $configManager;

    private WebsiteManager $websiteManager;

    /** @var Localization|null|bool */
    private $slugLocalization = false;

    public function __construct(
        UserLocalizationManagerInterface $localizationManager,
        RequestStack $requestStack,
        ManagerRegistry $registry,
        ConfigManager $configManager,
        WebsiteManager $websiteManager
    ) {
        $this->innerManager = $localizationManager;
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledLocalizations(): array
    {
        return $this->innerManager->getEnabledLocalizations();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocalization(): ?Localization
    {
        return $this->innerManager->getDefaultLocalization();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization(Website $website = null): ?Localization
    {
        if ($this->isSupported()) {
            if ($this->slugLocalization === false) {
                $request = $this->requestStack->getMainRequest();
                if ($request && $request->attributes->has('_used_slug')) {
                    /** @var Slug $usedSlug */
                    $usedSlug = $request->attributes->get('_used_slug');
                    $this->registry->getManagerForClass(Slug::class)?->refresh($usedSlug);
                    // save slug's localization after refresh to avoid the repeating of refresh query.
                    $this->slugLocalization = $usedSlug->getLocalization();
                    $this->assertLocalizationEnabled();
                }
            }
        }

        return $this->slugLocalization ?: $this->innerManager->getCurrentLocalization($website);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalizationByCustomerUser(
        CustomerUser $customerUser,
        Website $website = null
    ): ?Localization {
        return $this->innerManager->getCurrentLocalizationByCustomerUser($customerUser, $website);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentLocalization(Localization $localization, Website $website = null): void
    {
        $this->innerManager->setCurrentLocalization($localization, $website);
    }

    protected function isSupported(): bool
    {
        $website = $this->websiteManager->getCurrentWebsite();
        $this->configManager->setScopeIdFromEntity($website);
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::SWITCH_LOCALIZATION_BASED_ON_URL),
            Configuration::SWITCH_LOCALIZATION_BASED_ON_URL_DEFAULT_VALUE
        );
    }


    private function assertLocalizationEnabled(): void
    {
        if ($this->slugLocalization) {
            $localizationId = $this->slugLocalization->getId();
            if (!array_key_exists($localizationId, $this->innerManager->getEnabledLocalizations())) {
                $this->slugLocalization = null;
            }
        }
    }
}
