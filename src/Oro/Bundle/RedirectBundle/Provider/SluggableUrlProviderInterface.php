<?php

namespace Oro\Bundle\RedirectBundle\Provider;

/**
 * Defines the contract for providing URLs for sluggable entities.
 *
 * Implementations of this interface generate URLs for sluggable entities based on route names,
 * route parameters, and localization preferences. They support context-aware URL generation
 * and can be configured with additional context information to influence URL computation.
 */
interface SluggableUrlProviderInterface
{
    /**
     * Retrieves the URL from configured resources.
     * If URL cannot be computed, null will be returned.
     *
     * @param string $routeName
     * @param array $routeParameters
     * @param int|null $localizationId
     * @return null|string
     */
    public function getUrl($routeName, $routeParameters, $localizationId);

    /**
     * Additional criteria, that can alter logic of URL computing.
     *
     * @param string $contextUrl
     */
    public function setContextUrl($contextUrl);
}
