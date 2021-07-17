<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class CategoryRoutingInformationProvider implements RoutingInformationProviderInterface
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
        return $this->configManager->get('oro_catalog.category_direct_url_prefix');
    }
}
