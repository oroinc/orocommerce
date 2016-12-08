<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class PageRoutingInformationProvider implements RoutingInformationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof Page;
    }

    /**
     * @param Page $entity
     *
     * {@inheritdoc}
     */
    public function getRouteData($entity)
    {
        return new RouteData('oro_cms_frontend_page_view', ['id' => $entity->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlPrefix($entity)
    {
        return '';
    }
}
