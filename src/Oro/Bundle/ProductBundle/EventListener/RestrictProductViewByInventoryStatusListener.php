<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
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

    public function __construct(ConfigManager $configManager, ManagerRegistry $doctrine)
    {
        $this->configManager = $configManager;
        parent::__construct($doctrine);
    }

    #[\Override]
    protected function restrictProductView(Product $product, ControllerEvent $event): void
    {
        $allowedOptionIds = $this->configManager->get('oro_product.general_frontend_product_visibility');
        if ($product->getInventoryStatus()
            && !\in_array($product->getInventoryStatus()->getId(), $allowedOptionIds, true)
        ) {
            throw new AccessDeniedHttpException(sprintf(
                'Inventory status "%s" is configured as invisible. Product id: %d',
                $product->getInventoryStatus()->getId(),
                $product->getId()
            ));
        }
    }
}
