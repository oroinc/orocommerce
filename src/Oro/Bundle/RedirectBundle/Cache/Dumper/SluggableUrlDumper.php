<?php

namespace Oro\Bundle\RedirectBundle\Cache\Dumper;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
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
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var UrlCacheInterface
     */
    private $cache;

    /**
     * @var RoutingInformationProviderInterface
     */
    private $routingInformationProvider;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var WebsiteProviderInterface
     */
    private $websiteProvider;

    public function __construct(
        ManagerRegistry $registry,
        UrlCacheInterface $cache
    ) {
        $this->registry = $registry;
        $this->cache = $cache;
    }

    public function setRoutingInformationProvider(RoutingInformationProviderInterface $routingInformationProvider)
    {
        $this->routingInformationProvider = $routingInformationProvider;
    }

    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function setWebsiteProvider(WebsiteProviderInterface $websiteProvider)
    {
        $this->websiteProvider = $websiteProvider;
    }

    /**
     * @deprecated use dumpByEntity instead
     *
     * @param string $routeName
     * @param array $entityIds
     */
    public function dump($routeName, array $entityIds)
    {
        $repository = $this->registry->getManagerForClass(Slug::class)
            ->getRepository(Slug::class);

        foreach ($repository->getSlugDataForDirectUrls($entityIds) as $slug) {
            $this->cache->setUrl(
                $routeName,
                $slug['routeParameters'],
                $slug['url'],
                $slug['slugPrototype'],
                $slug['localization_id']
            );
        }

        if ($this->cache instanceof FlushableCache) {
            $this->cache->flushAll();
        }
    }

    public function dumpByEntity(SluggableInterface $entity)
    {
        $routeData = $this->routingInformationProvider->getRouteData($entity);
        $existingSlugs = $this->getExistingSlugsInfo($entity);
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

        if ($this->cache instanceof FlushableCache) {
            $this->cache->flushAll();
        }
    }

    private function getExistingSlugsInfo(SluggableInterface $entity): array
    {
        $existingSlugs = [];
        foreach ($entity->getSlugs() as $slug) {
            $localizationId = $slug->getLocalization() ? (int)$slug->getLocalization()->getId() : 0;
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
