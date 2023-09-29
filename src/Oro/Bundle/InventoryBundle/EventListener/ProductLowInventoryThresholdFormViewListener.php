<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

/**
 * Adds low inventory threshold information to the product view and edit pages.
 */
class ProductLowInventoryThresholdFormViewListener extends AbstractFallbackFieldsFormView
{
    public function onProductView(BeforeListRenderEvent $event): void
    {
        $product = $this->getEntityFromRequest(Product::class);
        if (!$product) {
            return;
        }

        if (!$this->fieldAclHelper->isFieldViewGranted($product, 'lowInventoryThreshold')) {
            return;
        }

        $this->addBlockToEntityView(
            $event,
            '@OroInventory/Product/lowInventoryThreshold.html.twig',
            $product,
            'oro.product.sections.inventory'
        );
    }

    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        $product = $event->getEntity();
        if (!$this->fieldAclHelper->isFieldAvailable($product, 'lowInventoryThreshold')) {
            return;
        }

        $this->addBlockToEntityEdit(
            $event,
            '@OroInventory/Product/lowInventoryThresholdFormWidget.html.twig',
            'oro.product.sections.inventory'
        );
    }
}
