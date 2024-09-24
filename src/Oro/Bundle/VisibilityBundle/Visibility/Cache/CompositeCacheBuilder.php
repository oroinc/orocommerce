<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

class CompositeCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var CacheBuilderInterface[]
     */
    protected $builders = [];

    public function addBuilder(CacheBuilderInterface $cacheBuilder)
    {
        if (!in_array($cacheBuilder, $this->builders, true)) {
            $this->builders[] = $cacheBuilder;
        }
    }

    #[\Override]
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        foreach ($this->builders as $builder) {
            if ($builder->isVisibilitySettingsSupported($visibilitySettings)) {
                $builder->resolveVisibilitySettings($visibilitySettings);
            }
        }
    }

    #[\Override]
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        foreach ($this->builders as $builder) {
            if ($builder->isVisibilitySettingsSupported($visibilitySettings)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function buildCache(Scope $scope = null)
    {
        foreach ($this->builders as $builder) {
            $builder->buildCache($scope);
        }
    }
}
