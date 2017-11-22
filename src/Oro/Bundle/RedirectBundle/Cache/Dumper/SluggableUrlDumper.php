<?php

namespace Oro\Bundle\RedirectBundle\Cache\Dumper;

use Doctrine\Common\Cache\FlushableCache;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;

class SluggableUrlDumper
{
    /**
     * @var SlugRepository
     */
    private $slugRepository;

    /**
     * @var UrlCacheInterface
     */
    private $cache;

    /**
     * @param SlugRepository $slugRepository
     * @param UrlCacheInterface $cache
     */
    public function __construct(SlugRepository $slugRepository, UrlCacheInterface $cache)
    {
        $this->slugRepository = $slugRepository;
        $this->cache = $cache;
    }

    /**
     * @param string $routeName
     * @param array $entityIds
     */
    public function dump($routeName, array $entityIds)
    {
        foreach ($this->slugRepository->getSlugDataForDirectUrls($entityIds) as $slug) {
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
