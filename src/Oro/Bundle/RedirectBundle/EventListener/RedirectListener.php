<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $localizedSlug = $this->getLocalizedSlug($usedSlug, $localization);

        $usedUrlParts = $this->getUsedContextParts($request);
        $usedUrlParts[] = $usedSlug->getUrl();
        $currentUrl = UrlUtil::join(...$usedUrlParts);

        $urlParts = $this->getContextParts($request, $localization);
        $urlParts[] = $localizedSlug ? $localizedSlug->getUrl(): $usedSlug->getUrl();
        $newUrl = UrlUtil::join(...$urlParts);

        // We can't compare entities because in some cases they can be different objects.
        if ($currentUrl !== $newUrl) {
            $url = $this->canonicalUrlGenerator->getAbsoluteUrl(
                $newUrl,
                $this->websiteManager->getCurrentWebsite()
            );
            $event->setResponse(new RedirectResponse($url));
        }
    }

    private function getLocalizedSlug(Slug $usedSlug, ?Localization $localization): ?Slug
    {
        $this->registry->getManagerForClass(Slug::class)->refresh($usedSlug);
        if ($usedSlug->getLocalization() === $localization) {
            return null;
        }

        $sourceEntity = $this->slugSourceEntityProvider->getSourceEntityBySlug($usedSlug);
        if (!$sourceEntity) {
            return null;
        }

        $defaultSlug = null;
        foreach ($sourceEntity->getSlugs() as $slug) {
            if ($slug->getLocalization() === $localization) {
                return $slug;
            }
            if (!$slug->getLocalization()) {
                $defaultSlug = $slug;
            }
        }

        return $defaultSlug;
    }

    /**
     * @param Request $request
     * @param Localization|null $localization
     * @return array|string[]
     */
    private function getContextParts(
        Request $request,
        ?Localization $localization
    ): array {
        $parts = [];
        if ($request->attributes->has('_context_url_attributes')) {
            $contextAttributes = $request->attributes->get('_context_url_attributes');
            foreach ($contextAttributes as $contextAttribute) {
                if (empty($contextAttribute['_used_slug'])) {
                    continue;
                }
                $contextUsedSlug = $contextAttribute['_used_slug'];
                $localizedContextSlug = $this->getLocalizedSlug($contextUsedSlug, $localization);
                if (!$localizedContextSlug) {
                    $localizedContextSlug = $contextUsedSlug;
                }

                $parts[] = $localizedContextSlug->getUrl();
                $parts[] = SluggableUrlGenerator::CONTEXT_DELIMITER;
            }
        }

        return $parts;
    }

    /**
     * @param Request $request
     * @return array|string[]
     */
    private function getUsedContextParts(
        Request $request
    ): array {
        $parts = [];
        if ($request->attributes->has('_context_url_attributes')) {
            $contextAttributes = $request->attributes->get('_context_url_attributes');
            foreach ($contextAttributes as $contextAttribute) {
                if (empty($contextAttribute['_used_slug'])) {
                    continue;
                }
                $contextUsedSlug = $contextAttribute['_used_slug'];
                $this->registry->getManagerForClass(Slug::class)->refresh($contextUsedSlug);

                $parts[] = $contextUsedSlug->getUrl();
                $parts[] = SluggableUrlGenerator::CONTEXT_DELIMITER;
            }
        }

        return $parts;
    }
}
