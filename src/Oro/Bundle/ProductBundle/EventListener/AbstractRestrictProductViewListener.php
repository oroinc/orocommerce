<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Abstract listener to disallow direct access to product view pages.
 */
abstract class AbstractRestrictProductViewListener
{
    protected function isApplicable(ControllerEvent $event): bool
    {
        $controller = $event->getController();

        return $controller
            && is_array($controller)
            && $controller[0] instanceof ProductController
            && $controller[1] === 'viewAction'
            && $event->getRequest()->attributes->has('product');
    }

    protected function getProduct(ControllerEvent $event): ?Product
    {
        $product = $event->getRequest()->attributes->get('product');
        if ($product instanceof Product) {
            return $product;
        }

        return null;
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $product = $this->getProduct($event);
        if (!$product) {
            return;
        }

        $this->restrictProductView($product, $event);
    }

    abstract protected function restrictProductView(Product $product, ControllerEvent $event);
}
