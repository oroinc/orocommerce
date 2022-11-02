<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * If human readable URL is not present in cache, read it from DB and save in cache
 */
class SluggableUrlDatabaseAwareProvider implements SluggableUrlProviderInterface
{
    const URL_KEY = 'url';
    const SLUG_PROTOTYPE_KEY = 'slug_prototype';
    const SLUG_ROUTES_KEY = UrlCacheInterface::SLUG_ROUTES_KEY;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SluggableUrlCacheAwareProvider
     */
    protected $urlCacheProvider;

    /**
     * @var UrlCacheInterface
     */
    protected $cache;

    /**
     * @var SlugRepository
     */
    protected $slugRepository;

    public function __construct(
        SluggableUrlCacheAwareProvider $urlCacheAwareProvider,
        UrlCacheInterface $cache,
        ManagerRegistry $registry
    ) {
        $this->urlCacheProvider = $urlCacheAwareProvider;
        $this->cache = $cache;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($routeName, $routeParameters, $localizationId)
    {
        if (!$routeName) {
            return null;
        }

        // Skip routes that does not have slugs
        $sluggableRoutes = $this->getSluggableRoutes();
        $isKnownRoute = array_key_exists($routeName, $sluggableRoutes);
        if ($isKnownRoute && $sluggableRoutes[$routeName] === false) {
            return null;
        }

        // Read URL from cache and return it if exists
        $url = $this->urlCacheProvider->getUrl($routeName, $routeParameters, $localizationId);

        if ($url !== false) {
            return $url;
        }

        $this->fillCacheByDatabase($routeName, $routeParameters, $localizationId);

        $url = $this->urlCacheProvider->getUrl($routeName, $routeParameters, $localizationId);

        if (!$isKnownRoute) {
            $sluggableRoutes[$routeName] = $url || $this->isSlugForRouteExists($routeName);

            $this->updateSluggableRoutes($sluggableRoutes);
        }

        if ($this->cache instanceof FlushableCacheInterface) {
            $this->cache->flushAll();
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function setContextUrl($contextUrl)
    {
        $this->urlCacheProvider->setContextUrl($contextUrl);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param int|null $localizationId
     */
    protected function fillCacheByDatabase($routeName, $routeParameters, $localizationId)
    {
        $slugData = $this->getSlugData($routeName, $routeParameters, $localizationId);

        // Store in the persistent cache to bypass database read in future
        // Store URL for requested localizationId even for cases where repository returns URL for default localization
        // to increase cache hits.
        // On slug changes caches should be refreshed during new Slug entities actualization
        // by queue processor DirectUrlProcessor
        $this->cache->setUrl(
            $routeName,
            $routeParameters,
            $slugData[self::URL_KEY],
            $slugData[self::SLUG_PROTOTYPE_KEY],
            $localizationId
        );
    }

    protected function getSlugData(string $routeName, array $routeParameters, ?int $localizationId): array
    {
        $slugData = $this->getSlugRepository()->getRawSlug(
            $routeName,
            $routeParameters,
            $localizationId
        );

        if (!$slugData) {
            $slugData = [
                self::URL_KEY => null,
                self::SLUG_PROTOTYPE_KEY => null
            ];
        }

        return $slugData;
    }

    protected function updateSluggableRoutes(array $sluggableRoutes)
    {
        $this->cache->setUrl(self::SLUG_ROUTES_KEY, [], json_encode($sluggableRoutes));
    }

    /**
     * @return array
     */
    protected function getSluggableRoutes()
    {
        $encodedSluggableRoutes = $this->cache->getUrl(self::SLUG_ROUTES_KEY, []);
        if ($encodedSluggableRoutes === false) {
            return [];
        }

        return (array)json_decode($encodedSluggableRoutes, true);
    }

    /**
     * @return SlugRepository
     */
    protected function getSlugRepository()
    {
        if (!$this->slugRepository) {
            $this->slugRepository = $this->registry
                ->getManagerForClass(Slug::class)
                ->getRepository(Slug::class);
        }

        return $this->slugRepository;
    }

    /**
     * @param string $routeName
     *
     * @return bool
     */
    protected function isSlugForRouteExists($routeName): bool
    {
        return $this->getSlugRepository()->isSlugForRouteExists($routeName);
    }
}
