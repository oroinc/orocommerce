<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CompositeCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var CacheBuilderInterface[]
     */
    protected $builders = [];

    /**
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function addBuilder(CacheBuilderInterface $cacheBuilder)
    {
        if (!in_array($cacheBuilder, $this->builders, true)) {
            $this->builders[] = $cacheBuilder;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        foreach ($this->builders as $builder) {
            if ($builder->isVisibilitySettingsSupported($visibilitySettings)) {
                $builder->resolveVisibilitySettings($visibilitySettings);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        foreach ($this->builders as $builder) {
            if ($builder->isVisibilitySettingsSupported($visibilitySettings)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        foreach ($this->builders as $builder) {
            $builder->buildCache($website);
        }
    }
}
