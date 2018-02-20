<?php

namespace Oro\Bundle\OrderBundle\EventListener\OrderLineItem;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;

/**
 * Listener saves variant field values of configurable product to OrderLineItem.
 * It will help to display order information when product deleted.
 */
class OrderLineItemEventListener
{
    /** @var ConfigurableProductProvider */
    protected $configurableProductProvider;

    /**
     * @param ConfigurableProductProvider $configurableProductProvider
     */
    public function __construct(ConfigurableProductProvider $configurableProductProvider)
    {
        $this->configurableProductProvider = $configurableProductProvider;
    }

    /**
     * @param OrderLineItem $lineItem
     * @param LifecycleEventArgs $event
     */
    public function prePersist(OrderLineItem $lineItem, LifecycleEventArgs $event)
    {
        $this->updateProductVariantFields($lineItem);
    }

    /**
     * @param OrderLineItem $lineItem
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event)
    {
        $this->updateProductVariantFields($lineItem);
    }

    /**
     * @param OrderLineItem $lineItem
     */
    protected function updateProductVariantFields(OrderLineItem $lineItem)
    {
        $product = $lineItem->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }

        $variantFieldNames = $this->configurableProductProvider->getLineItemProduct($lineItem);
        if (!$variantFieldNames || !isset($variantFieldNames[$product->getId()])) {
            return;
        }

        $lineItem->setProductVariantFields($variantFieldNames[$product->getId()]);
    }
}
