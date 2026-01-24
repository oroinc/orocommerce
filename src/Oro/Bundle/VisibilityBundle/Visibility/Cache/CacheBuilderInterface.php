<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

/**
 * Defines the contract for visibility cache builders.
 *
 * Implementations of this interface are responsible for building and maintaining the resolved visibility cache,
 * which stores the final computed visibility state for products and categories. The cache resolves visibility
 * fallback chains (e.g., customer -> customer group -> all customers -> config) to improve query performance.
 */
interface CacheBuilderInterface
{
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings);

    /**
     * @param VisibilityInterface $visibilitySettings
     * @return mixed
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings);

    /**
     * @param Scope|null $scope
     * @return mixed
     */
    public function buildCache(?Scope $scope = null);
}
