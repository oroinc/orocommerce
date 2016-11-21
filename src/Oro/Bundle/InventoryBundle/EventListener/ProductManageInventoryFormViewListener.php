<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Fallback\AbstractFallbackFieldsFormView;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductManageInventoryFormViewListener extends AbstractFallbackFieldsFormView
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $product = $this->getEntityFromRequest(Product::class);
        if (!$product) {
            return;
        }

        $this->onEntityView($event, 'OroInventoryBundle:Product:manageInventory.html.twig', $product);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $this->onEntityEdit($event, 'OroInventoryBundle:Product:manageInventoryFormWidget.html.twig');
    }
}
