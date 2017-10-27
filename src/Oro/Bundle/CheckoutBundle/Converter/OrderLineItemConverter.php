<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Converts Order line items to CheckoutLineItems.
 */
class OrderLineItemConverter implements CheckoutLineItemConverterInterface
{
    /** @var InventoryQuantityProviderInterface */
    protected $quantityProvider;

    /**
     * @param InventoryQuantityProviderInterface $quantityProvider
     */
    public function __construct(InventoryQuantityProviderInterface $quantityProvider)
    {
        $this->quantityProvider = $quantityProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isSourceSupported($source)
    {
        return $source instanceof Order;
    }

    /**
     * @param Order $source
     * {@inheritDoc}
     */
    public function convert($source)
    {
        $lineItems = $source->getLineItems();
        $checkoutLineItems = new ArrayCollection();

        foreach ($lineItems as $lineItem) {
            $availableQuantity = $this->getAvailableProductQuantity($lineItem);
            if (!$availableQuantity) {
                continue;
            }
            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem
                ->setFromExternalSource($lineItem->isFromExternalSource())
                ->setPriceFixed(false)
                ->setProduct($lineItem->getProduct())
                ->setParentProduct($lineItem->getParentProduct())
                ->setFreeFormProduct($lineItem->getFreeFormProduct())
                ->setProductSku($lineItem->getProductSku())
                ->setProductUnit($lineItem->getProductUnit())
                ->setProductUnitCode($lineItem->getProductUnitCode())
                // use only available quantity of the product
                ->setQuantity(min($availableQuantity, $lineItem->getQuantity()))
                ->setPrice($lineItem->getPrice())
                ->setPriceType($lineItem->getPriceType())
                ->setComment($lineItem->getComment());
            $checkoutLineItems->add($checkoutLineItem);
        }

        return $checkoutLineItems;
    }

    /**
     * @param ProductLineItemInterface $lineItem
     * @return int
     */
    protected function getAvailableProductQuantity(ProductLineItemInterface $lineItem)
    {
        if (!$this->quantityProvider->canDecrement($lineItem->getProduct())) {
            return $lineItem->getQuantity();
        }

        return $this->quantityProvider->getAvailableQuantity($lineItem->getProduct(), $lineItem->getProductUnit());
    }
}
