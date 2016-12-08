<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class CategoryRoutingInformationProvider implements RoutingInformationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof Category;
    }

    /**
     * @param Category $entity
     *
     * {@inheritdoc}
     */
    public function getRouteData($entity)
    {
        return new RouteData(
            'oro_product_frontend_product_index',
            [
                'categoryId' => $entity->getId(),
                'includeSubcategories' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlPrefix($entity)
    {
        return '';
    }
}
