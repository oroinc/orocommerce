<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * If human readable URL is not present in cache, read it from DB and save in cache
 */
class SluggableUrlDatabaseAwareProvider implements SluggableUrlProviderInterface
{
    const URL_KEY = 'url';
    const SLUG_PROTOTYPE_KEY = 'slug_prototype';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SluggableUrlCacheAwareProvider
     */
    protected $urlCacheProvider;

    /**
     * @var UrlStorageCache
     */
    protected $cache;

    /**
     * @param SluggableUrlCacheAwareProvider $urlCacheAwareProvider
     * @param UrlStorageCache $cache
     * @param ManagerRegistry $registry
     */
    public function __construct(
        SluggableUrlCacheAwareProvider $urlCacheAwareProvider,
        UrlStorageCache $cache,
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
        // Read URL from cache and return it if exists
        $url = $this->urlCacheProvider->getUrl($routeName, $routeParameters, $localizationId);

        if ($url) {
            return $url;
        }

        // check if URL is marked as no slug version
        if ($url === false) {
            // database read is expensive, therefore protecting against repeated calls
            return null;
        }

        $this->fillCacheByDatabase($routeName, $routeParameters, $localizationId);

        return $this->urlCacheProvider->getUrl($routeName, $routeParameters, $localizationId);
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

        // store in the persistent cache to bypass database read in future
        $dataStorage = $this->cache->getUrlDataStorage($routeName, $routeParameters);

        $dataStorage->setUrl(
            $routeParameters,
            $slugData[self::URL_KEY],
            $slugData[self::SLUG_PROTOTYPE_KEY],
            $localizationId
        );

        $this->cache->flush();
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param int|null $localizationId
     * @return array|null
     */
    protected function getSlugData($routeName, $routeParameters, $localizationId)
    {
        /** @var SlugRepository $slugRepository */
        $slugRepository = $this->registry
            ->getManagerForClass(Slug::class)
            ->getRepository(Slug::class);

        $slugData = $slugRepository->getRawSlug(
            $routeName,
            $routeParameters,
            $localizationId
        );

        if (!$slugData) {
            $slugData = [
                self::URL_KEY => false,
                self::SLUG_PROTOTYPE_KEY => false
            ];
        }

        return $slugData;
    }
}
