<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class ProductRoutingInformationProvider implements RoutingInformationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof Product;
    }

    /**
     * @param Product $entity
     *
     * {@inheritdoc}
     */
    public function getRouteData($entity)
    {
        return new RouteData('oro_product_frontend_product_view', ['id' => $entity->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlPrefix($entity)
    {
        return '';
    }
}
