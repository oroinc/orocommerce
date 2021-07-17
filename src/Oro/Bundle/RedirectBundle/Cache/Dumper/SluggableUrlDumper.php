<?php

namespace Oro\Bundle\RedirectBundle\Cache\Dumper;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;

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

    public function __construct(ManagerRegistry $registry, UrlCacheInterface $cache)
    {
        $this->registry = $registry;
        $this->cache = $cache;
    }

    /**
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
}
