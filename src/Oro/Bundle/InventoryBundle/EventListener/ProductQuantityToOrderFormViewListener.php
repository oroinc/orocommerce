<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

/**
 * Adds minimum/maximum quantity to order information to the product view and edit pages.
 */
class ProductQuantityToOrderFormViewListener extends AbstractFallbackFieldsFormView
{
    public function onProductView(BeforeListRenderEvent $event): void
    {
        $product = $this->getEntityFromRequest(Product::class);
        if (!$product) {
            return;
        }

        if ($this->fieldAclHelper->isFieldViewGranted($product, 'minimumQuantityToOrder')) {
            $this->addBlockToEntityView(
                $event,
                '@OroInventory/Product/viewMinimumQuantityToOrder.html.twig',
                $product,
                'oro.product.sections.inventory'
            );
        }

        if ($this->fieldAclHelper->isFieldViewGranted($product, 'maximumQuantityToOrder')) {
            $this->addBlockToEntityView(
                $event,
                '@OroInventory/Product/viewMaximumQuantityToOrder.html.twig',
                $product,
                'oro.product.sections.inventory'
            );
        }
    }

    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        $product = $event->getEntity();
        if ($this->fieldAclHelper->isFieldAvailable($product, 'minimumQuantityToOrder')) {
            $this->addBlockToEntityEdit(
                $event,
                '@OroInventory/Product/editMinimumQuantityToOrder.html.twig',
                'oro.product.sections.inventory'
            );
        }

        if ($this->fieldAclHelper->isFieldAvailable($product, 'maximumQuantityToOrder')) {
            $this->addBlockToEntityEdit(
                $event,
                '@OroInventory/Product/editMaximumQuantityToOrder.html.twig',
                'oro.product.sections.inventory'
            );
        }
    }
}
