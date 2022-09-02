<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\RedirectBundle\Routing\Router;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Routing\UrlUtil;

/**
 * This class help to return a URL with appropriate localized slug if given URL is using a slug.
 */
class LocalizedSlugRedirectHelper
{
    protected SlugSourceEntityProviderInterface $slugSourceEntityProvider;

    protected ManagerRegistry $registry;

    protected CanonicalUrlGenerator $canonicalUrlGenerator;

    protected WebsiteManager $websiteManager;

    protected Router $router;

    public function __construct(
        SlugSourceEntityProviderInterface $slugSourceEntityProvider,
        ManagerRegistry $registry,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        WebsiteManager $websiteManager,
        Router $router
    ) {
        $this->slugSourceEntityProvider = $slugSourceEntityProvider;
        $this->registry = $registry;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->websiteManager = $websiteManager;
        $this->router = $router;
    }

    public function getLocalizedUrl(string $url, Localization $localization): string
    {
        $pathInfo = parse_url($url, PHP_URL_PATH);
        $attributes = $this->router->match($pathInfo);
        if (isset($attributes['_used_slug'])) {
            $usedSlug = $attributes['_used_slug'];
            $usedUrlParts = $this->getContextParts($attributes);
            $usedUrlParts[] = $usedSlug->getUrl();
            $currentUrl = UrlUtil::join(...$usedUrlParts);

            $localizedSlug = $this->getLocalizedSlug($usedSlug, $localization);
            $urlParts = $this->getContextParts($attributes, $localization);
            $urlParts[] = $localizedSlug ? $localizedSlug->getUrl() : $usedSlug->getUrl();
            $newUrl = UrlUtil::join(...$urlParts);

            // We can't compare entities because in some cases they can be different objects.
            if ($currentUrl !== $newUrl) {
                $url = $this->canonicalUrlGenerator->getAbsoluteUrl(
                    $newUrl,
                    $this->websiteManager->getCurrentWebsite()
                );
            }
        }

        return $url;
    }

    private function getLocalizedSlug(Slug $usedSlug, ?Localization $localization): ?Slug
    {
        $this->registry->getManagerForClass(Slug::class)?->refresh($usedSlug);
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

    private function getContextParts(array $requestAttributes, ?Localization $localization = null): array
    {
        $parts = [];
        if (isset($requestAttributes['_context_url_attributes'])) {
            foreach ($requestAttributes['_context_url_attributes'] as $contextAttribute) {
                if (empty($contextAttribute['_used_slug'])) {
                    continue;
                }

                $contextUsedSlug = $contextAttribute['_used_slug'];

                $localizedContextSlug = null;
                if ($localization !== null) {
                    $localizedContextSlug = $this->getLocalizedSlug($contextUsedSlug, $localization);
                }
                if (!$localizedContextSlug) {
                    $localizedContextSlug = $contextUsedSlug;
                }

                $parts[] = $localizedContextSlug->getUrl();
                $parts[] = SluggableUrlGenerator::CONTEXT_DELIMITER;
            }
        }

        return $parts;
    }
}
