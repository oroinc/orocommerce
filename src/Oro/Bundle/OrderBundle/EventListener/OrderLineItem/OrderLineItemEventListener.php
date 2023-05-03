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
    /** @var ConfigurableProductProvider */
    protected $configurableProductProvider;

    public function __construct(ConfigurableProductProvider $configurableProductProvider)
    {
        $this->configurableProductProvider = $configurableProductProvider;
    }

    public function prePersist(OrderLineItem $lineItem, LifecycleEventArgs $event)
    {
        $this->updateProductVariantFields($lineItem);
    }

    public function preUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event)
    {
        $this->updateProductVariantFields($lineItem);
    }

    protected function updateProductVariantFields(OrderLineItem $lineItem)
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
