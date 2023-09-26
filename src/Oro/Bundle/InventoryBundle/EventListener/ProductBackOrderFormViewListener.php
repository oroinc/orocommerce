<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

/**
 * Adds Product Back Order information to the product view and edit pages.
 */
class ProductBackOrderFormViewListener extends AbstractFallbackFieldsFormView
{
    public function onProductView(BeforeListRenderEvent $event): void
    {
        $product = $this->getEntityFromRequest(Product::class);
        if (!$product) {
            return;
        }

        if (!$this->fieldAclHelper->isFieldViewGranted($product, 'backOrder')) {
            return;
        }

        $this->addBlockToEntityView(
            $event,
            '@OroInventory/Product/backOrder.html.twig',
            $product,
            'oro.product.sections.inventory'
        );
    }

    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        $product = $event->getEntity();
        if (!$this->fieldAclHelper->isFieldAvailable($product, 'backOrder')) {
            return;
        }

        $this->addBlockToEntityEdit(
            $event,
            '@OroInventory/Product/backOrderFormWidget.html.twig',
            'oro.product.sections.inventory'
        );
    }
}
