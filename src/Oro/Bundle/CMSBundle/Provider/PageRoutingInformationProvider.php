<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

/**
 * Provides routing information for CMS pages.
 *
 * Implements the routing information provider interface to supply route data and URL prefixes
 * for CMS page entities, enabling proper URL generation and routing in the storefront.
 */
class PageRoutingInformationProvider implements RoutingInformationProviderInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function isSupported($entity)
    {
        return $entity instanceof Page;
    }

    /**
     * @param Page $entity
     *
     */
    #[\Override]
    public function getRouteData($entity)
    {
        return new RouteData('oro_cms_frontend_page_view', ['id' => $entity->getId()]);
    }

    #[\Override]
    public function getUrlPrefix($entity)
    {
        return $this->configManager->get('oro_cms.landing_page_direct_url_prefix');
    }
}
