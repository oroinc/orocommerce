<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

/**
 * Adds upcoming information to the product view and edit pages.
 */
class ProductUpcomingFormViewListener extends AbstractFallbackFieldsFormView
{
    public function onProductView(BeforeListRenderEvent $event): void
    {
        $product = $event->getEntity();
        if (!$product instanceof Product) {
            return;
        }

        if (!$this->fieldAclHelper->isFieldViewGranted($product, 'isUpcoming')) {
            return;
        }

        $this->addBlockToEntityView(
            $event,
            '@OroInventory/Product/upcoming_view.html.twig',
            $product,
            'oro.product.sections.inventory'
        );
    }

    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        if (!$this->fieldAclHelper->isFieldAvailable($event->getEntity(), 'isUpcoming')) {
            return;
        }

        $this->addBlockToEntityEdit(
            $event,
            '@OroInventory/Product/upcoming_edit.html.twig',
            'oro.product.sections.inventory'
        );
    }
}
