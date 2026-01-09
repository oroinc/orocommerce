<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

/**
 * Provides routing information for brand entities.
 *
 * This provider supplies route data for brand pages, enabling the redirect bundle
 * to generate URLs for brand entities based on system configuration and routing rules.
 */
class BrandRoutingInformationProvider implements RoutingInformationProviderInterface
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
        return $entity instanceof Brand;
    }

    /**
     * @param Product $entity
     *
     */
    #[\Override]
    public function getRouteData($entity)
    {
        return new RouteData('oro_product_frontend_brand_view', ['id' => $entity->getId()]);
    }

    #[\Override]
    public function getUrlPrefix($entity)
    {
        return $this->configManager->get('oro_product.brand_direct_url_prefix');
    }
}
