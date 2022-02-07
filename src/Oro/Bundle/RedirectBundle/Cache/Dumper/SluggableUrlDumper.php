<?php

namespace Oro\Bundle\RedirectBundle\Cache\Dumper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Routing\RouteData;

/**
 * Dumps sluggable urls to cache.
 */
class SluggableUrlDumper
{
    private UrlCacheInterface $cache;
    private RoutingInformationProviderInterface $routingInformationProvider;
    private ConfigManager $configManager;
    private WebsiteProviderInterface $websiteProvider;

    public function __construct(
        UrlCacheInterface $cache,
        RoutingInformationProviderInterface $routingInformationProvider,
        ConfigManager $configManager,
        WebsiteProviderInterface $websiteProvider
    ) {
        $this->cache = $cache;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->configManager = $configManager;
        $this->websiteProvider = $websiteProvider;
    }

    public function dump(SluggableInterface $entity)
    {
        $routeData = $this->routingInformationProvider->getRouteData($entity);
        $existingSlugs = $this->getExisitngSlugsInfo($entity);
        $localizationIds = $this->getEnabledLocalizationIds();
        $baseUrlInfo = $existingSlugs[SluggableUrlGenerator::DEFAULT_LOCALIZATION_ID] ?? null;
        if ($baseUrlInfo) {
            // Default localization is never requested from cache, no need to save it there.
            unset($existingSlugs[SluggableUrlGenerator::DEFAULT_LOCALIZATION_ID]);
        } else {
            $localizationsWithoutTranslations = array_diff($localizationIds, array_keys($existingSlugs));
            $this->removeFilledSlugCache($routeData, $localizationsWithoutTranslations);
        }

        foreach ($existingSlugs as $localizationId => $urlInfo) {
            $this->cache->setUrl(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $urlInfo[0],
                $urlInfo[1],
                $localizationId
            );
        }

        if ($baseUrlInfo) {
            $this->fillSlugCacheWithBaseSlug($routeData, $baseUrlInfo, $localizationIds, array_keys($existingSlugs));
        }

        if ($this->cache instanceof FlushableCacheInterface) {
            $this->cache->flushAll();
        }
    }

    private function getExisitngSlugsInfo(SluggableInterface $entity): array
    {
        $existingSlugs = [];
        foreach ($entity->getSlugs() as $slug) {
            $localizationId = (int)$slug->getLocalization()?->getId();
            $existingSlugs[$localizationId] = [$slug->getUrl(), $slug->getSlugPrototype()];
        }

        return $existingSlugs;
    }

    /**
     * Fill slugs cache with base slug for localizations that has no special slug
     * This will make cache bigger, but will improve cache hit ratio
     */
    private function fillSlugCacheWithBaseSlug(
        RouteData $routeData,
        array $baseSlugUrlInfo,
        array $knownLocalizationIds,
        array $localizationIdsWithSlugs
    ): void {
        foreach (array_diff($knownLocalizationIds, $localizationIdsWithSlugs) as $localizationId) {
            $this->cache->setUrl(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $baseSlugUrlInfo[0],
                $baseSlugUrlInfo[1],
                $localizationId
            );
        }
    }

    private function removeFilledSlugCache(RouteData $routeData, array $localizationIds): void
    {
        foreach ($localizationIds as $localizationId) {
            $this->cache->removeUrl(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $localizationId
            );
        }
    }

    private function getEnabledLocalizationIds(): array
    {
        $websites = $this->websiteProvider->getWebsites();
        $localizationIds = $this->configManager->getValues(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            $websites
        );
        if (!$localizationIds) {
            return [];
        }

        return array_unique(array_merge(...$localizationIds));
    }
}
