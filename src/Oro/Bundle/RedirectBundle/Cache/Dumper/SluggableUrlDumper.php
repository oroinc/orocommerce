<?php

namespace Oro\Bundle\RedirectBundle\Cache\Dumper;

use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;

class SluggableUrlDumper
{
    /**
     * @var SlugRepository
     */
    private $slugRepository;

    /**
     * @var UrlStorageCache
     */
    private $cache;

    /**
     * @param SlugRepository $slugRepository
     * @param UrlStorageCache $cache
     */
    public function __construct(SlugRepository $slugRepository, UrlStorageCache $cache)
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
            $this->cache->setUrl($routeName, $slug['routeParameters'], $slug['url'], $slug['slugPrototype']);
        }

        $this->cache->flush();
    }
}
