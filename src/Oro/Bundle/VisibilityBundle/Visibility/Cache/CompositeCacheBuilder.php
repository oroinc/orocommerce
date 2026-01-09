<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

/**
 * Composite cache builder that delegates visibility cache operations to multiple child builders.
 *
 * This class implements the composite pattern to manage multiple visibility cache builders, allowing
 * different cache building strategies to be combined. It delegates cache building operations to all
 * registered child builders that support the given visibility settings.
 */
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
    public function buildCache(?Scope $scope = null)
    {
        foreach ($this->builders as $builder) {
            $builder->buildCache($scope);
        }
    }
}
