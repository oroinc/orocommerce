<?php

namespace Oro\Bundle\RedirectBundle\Provider;

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
