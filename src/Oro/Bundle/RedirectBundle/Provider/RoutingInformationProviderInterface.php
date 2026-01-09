<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Component\Routing\RouteData;

/**
 * Defines the contract for providing routing information for entities.
 *
 * Implementations of this interface determine whether an entity is supported for routing,
 * extract route data from the entity, and provide URL prefixes. This information is used
 * to generate appropriate URLs and slugs for entities in the redirect system.
 */
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
