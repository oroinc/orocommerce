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
    private ProductKitItemLineItemConverter $productKitItemLineItemConverter;

    public function __construct(ProductKitItemLineItemConverter $productKitItemLineItemConverter)
    {
        $this->productKitItemLineItemConverter = $productKitItemLineItemConverter;
    }

    #[\Override]
    public function isSourceSupported($source)
    {
        return $source instanceof ShoppingList;
    }

    /**
     * @param ShoppingList $source
     */
    #[\Override]
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

            if ($lineItem->getProduct()?->isKit()) {
                foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                    $checkoutLineItem->addKitItemLineItem(
                        $this->productKitItemLineItemConverter->convert($kitItemLineItem)
                    );
                }
            }

            $checkoutLineItems->add($checkoutLineItem);
        }

        return $checkoutLineItems;
    }
}
