<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

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
        return $this->configManager->get('oro_cms.landing_page_direct_url_prefix');
    }
}
