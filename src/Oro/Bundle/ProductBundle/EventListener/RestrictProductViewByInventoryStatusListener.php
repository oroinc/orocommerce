<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Disallow direct access to product view pages with disabled inventory statuses.
 */
class RestrictProductViewByInventoryStatusListener
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if ($controller && $controller[0] instanceof ProductController && $controller[1] === 'viewAction'
            && $event->getRequest()->attributes->has('product')
        ) {
            $allowedStatuses = $this->configManager->get('oro_product.general_frontend_product_visibility');
            /** @var Product $product */
            $product = $event->getRequest()->attributes->get('product');
            if ($product instanceof Product && $product->getInventoryStatus()
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
}
