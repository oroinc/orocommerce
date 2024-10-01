<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class ProductRoutingInformationProvider implements RoutingInformationProviderInterface
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
        return $entity instanceof Product;
    }

    /**
     * @param Product $entity
     *
     */
    #[\Override]
    public function getRouteData($entity)
    {
        return new RouteData('oro_product_frontend_product_view', ['id' => $entity->getId()]);
    }

    #[\Override]
    public function getUrlPrefix($entity)
    {
        return $this->configManager->get('oro_product.product_direct_url_prefix');
    }
}
