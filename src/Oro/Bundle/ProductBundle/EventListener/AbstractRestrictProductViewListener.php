<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Abstract listener to disallow direct access to product view pages.
 */
abstract class AbstractRestrictProductViewListener
{
    public function __construct(
        protected ManagerRegistry $doctrine
    ) {
    }

    protected function isApplicable(ControllerEvent $event): bool
    {
        $controller = $event->getController();

        return $controller
            && is_array($controller)
            && $controller[0] instanceof ProductController
            && $controller[1] === 'viewAction';
    }

    protected function getProduct(ControllerEvent $event): ?Product
    {
        if ($event->getRequest()->attributes->has('product')) {
            $product = $event->getRequest()->attributes->get('product');
        } else {
            $product = $this->doctrine->getManager()
                ->getRepository(Product::class)
                ->find($event->getRequest()->attributes->get('id'));
        }

        if ($product instanceof Product) {
            return $product;
        }

        return null;
    }

    public function onKernelController(ControllerEvent $event): void
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

    abstract protected function restrictProductView(Product $product, ControllerEvent $event): void;
}
