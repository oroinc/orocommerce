<?php

namespace Oro\Bundle\OrderBundle\EventListener\OrderLineItem;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;

/**
 * Listener saves variant field values of configurable product to OrderLineItem.
 * It will help to display order information when product deleted.
 */
class OrderLineItemEventListener
{
    public function __construct(
        private ConfigurableProductProvider $configurableProductProvider
    ) {
    }

    public function prePersist(OrderLineItem $lineItem, LifecycleEventArgs $event): void
    {
        $this->updateProductVariantFields($lineItem);
    }

    public function preUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event): void
    {
        $this->updateProductVariantFields($lineItem);
    }

    protected function updateProductVariantFields(OrderLineItem $lineItem): void
    {
        $product = $lineItem->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }

        $variantFieldNames = $this->configurableProductProvider->getVariantFieldsValuesForLineItem($lineItem, false);
        if (!$variantFieldNames || !isset($variantFieldNames[$product->getId()])) {
            return;
        }

        $lineItem->setProductVariantFields($variantFieldNames[$product->getId()]);
    }
}
