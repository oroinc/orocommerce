<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
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
                ->setFromExternalSource(false)
                ->setPriceFixed(false)
                ->setProduct($lineItem->getProduct())
                ->setParentProduct($lineItem->getParentProduct())
                ->setProductSku($lineItem->getProductSku())
                ->setProductUnit($lineItem->getProductUnit())
                ->setProductUnitCode($lineItem->getProductUnitCode())
                ->setQuantity($lineItem->getQuantity())
                ->setComment($lineItem->getNotes())
                ->setChecksum($lineItem->getChecksum());

            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                $checkoutKitItemLineItem = (new CheckoutProductKitItemLineItem())
                    ->setProduct($kitItemLineItem->getProduct())
                    ->setKitItem($kitItemLineItem->getKitItem())
                    ->setUnit($kitItemLineItem->getUnit())
                    ->setQuantity($kitItemLineItem->getQuantity())
                    ->setSortOrder($kitItemLineItem->getSortOrder())
                    ->setPriceFixed(false);

                $checkoutLineItem->addKitItemLineItem($checkoutKitItemLineItem);
            }

            $checkoutLineItems->add($checkoutLineItem);
        }

        return $checkoutLineItems;
    }
}
