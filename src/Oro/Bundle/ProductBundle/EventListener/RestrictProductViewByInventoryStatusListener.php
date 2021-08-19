<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Disallow direct access to product view pages with disabled inventory statuses.
 */
class RestrictProductViewByInventoryStatusListener extends AbstractRestrictProductViewListener
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    protected function restrictProductView(Product $product, ControllerEvent $event)
    {
        $allowedStatuses = $this->configManager->get('oro_product.general_frontend_product_visibility');
        if ($product->getInventoryStatus()
            && !\in_array($product->getInventoryStatus()->getId(), $allowedStatuses, true)
        ) {
            throw new AccessDeniedHttpException(sprintf(
                'Inventory status "%s" is configured as invisible. Product id: %d',
                $product->getInventoryStatus()->getId(),
                $product->getId()
            ));
        }
    }
}
