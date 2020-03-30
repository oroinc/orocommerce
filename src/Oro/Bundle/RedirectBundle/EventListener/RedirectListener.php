<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Redirect listener for the slug with current localization
 */
class RedirectListener
{
    /**
     * @var UserLocalizationManagerInterface
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
     * @param UserLocalizationManagerInterface $userLocalizationManager
     * @param SlugSourceEntityProviderInterface $slugSourceEntityProvider
     * @param ManagerRegistry $registry
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        UserLocalizationManagerInterface $userLocalizationManager,
        SlugSourceEntityProviderInterface $slugSourceEntityProvider,
        ManagerRegistry $registry,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        WebsiteManager $websiteManager
    ) {
        $this->userLocalizationManager = $userLocalizationManager;
        $this->slugSourceEntityProvider = $slugSourceEntityProvider;
        $this->registry = $registry;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param RequestEvent $event
     */
    public function onRequest(RequestEvent $event)
    {
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
