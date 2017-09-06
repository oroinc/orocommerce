<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Converts ShoppingList line items to CheckoutLineItems.
 */
class ShoppingListLineItemConverter implements CheckoutLineItemConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function isSourceSupported($source)
    {
        return $source instanceof ShoppingList;
    }

    /**
     * @param ShoppingList $source
     * {@inheritDoc}
     */
    public function convert($source)
    {
        $lineItems = $source->getLineItems();
        $checkoutLineItems = new ArrayCollection();

        foreach ($lineItems as $lineItem) {
            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem
                ->setProduct($lineItem->getProduct())
                ->setProductSku($lineItem->getProductSku())
                ->setParentProduct($lineItem->getParentProduct())
                ->setProductUnit($lineItem->getProductUnit())
                ->setProductUnitCode($lineItem->getProductUnitCode())
                ->setComment($lineItem->getNotes())
                ->setQuantity($lineItem->getQuantity())
                ->setPriceFixed(false);
            $checkoutLineItems->add($checkoutLineItem);
        }

        return $checkoutLineItems;
    }
}
