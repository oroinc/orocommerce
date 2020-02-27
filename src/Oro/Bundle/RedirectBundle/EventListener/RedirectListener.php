<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Redirect listener for the slug with current localization
 */
class RedirectListener
{
    /**
     * @var UserLocalizationManager
     */
    protected $userLocalizationManager;

    /**
     * @var SlugSourceEntityProviderInterface
     */
    protected $slugSourceEntityProvider;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CanonicalUrlGenerator
     */
    protected $canonicalUrlGenerator;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param UserLocalizationManager $userLocalizationManager
     * @param SlugSourceEntityProviderInterface $slugSourceEntityProvider
     * @param ManagerRegistry $registry
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param WebsiteManager $websiteManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        UserLocalizationManager $userLocalizationManager,
        SlugSourceEntityProviderInterface $slugSourceEntityProvider,
        ManagerRegistry $registry,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        WebsiteManager $websiteManager,
        ConfigManager $configManager
    ) {
        $this->userLocalizationManager = $userLocalizationManager;
        $this->slugSourceEntityProvider = $slugSourceEntityProvider;
        $this->registry = $registry;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->websiteManager = $websiteManager;
        $this->configManager = $configManager;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$this->configManager->get('oro_redirect.language_switcher_uses_localized_urls')) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->has('_used_slug')) {
            return;
        }

        $localization = $this->userLocalizationManager->getCurrentLocalization();
        if (!$localization) {
            return;
        }

        $usedSlug = $request->attributes->get('_used_slug');
        $this->registry->getManagerForClass(Slug::class)->refresh($usedSlug);
        if ($usedSlug->getLocalization() === $localization) {
            return;
        }

        $sourceEntity = $this->slugSourceEntityProvider->getSourceEntityBySlug($usedSlug);
        if (!$sourceEntity) {
            return;
        }

        $filteredCollection = $sourceEntity->getSlugs()->filter(function (Slug $element) use ($localization) {
            return $element->getLocalization() === $localization;
        });

        if ($filteredCollection->count()) {
            $localizedSlug = $filteredCollection->first();
        } else {
            $localizedSlug = $sourceEntity->getSlugs()->filter(function (Slug $element) {
                return !$element->getLocalization();
            })->first();
        }

        // We can't compare entities because in some cases they can be different objects.
        if ($localizedSlug && $localizedSlug->getUrl() !== $usedSlug->getUrl()) {
            $url = $this->canonicalUrlGenerator->getAbsoluteUrl(
                $localizedSlug->getUrl(),
                $this->websiteManager->getCurrentWebsite()
            );
            $event->setResponse(new RedirectResponse($url));

            return;
        }
    }
}
