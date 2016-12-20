<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Component\Routing\RouteData;

interface RoutingInformationProviderInterface
{
    /**
     * If given entity is supported.
     *
     * @param object $entity
     * @return bool
     */
    public function isSupported($entity);

    /**
     * Get route data based on given entity.
     *
     * @param object $entity
     * @return RouteData
     */
    public function getRouteData($entity);

    /**
     * Get URL prefix by given entity.
     *
     * @param object $entity
     * @return string
     */
    public function getUrlPrefix($entity);
}
