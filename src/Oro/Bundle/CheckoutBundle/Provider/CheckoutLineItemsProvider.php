<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * This service provides some supporting information about line items
 * that will be helpful during checkout creation and processing.
 */
class CheckoutLineItemsProvider
{
    private CheckoutLineItemsManager $checkoutLineItemsManager;

    public function __construct(CheckoutLineItemsManager $checkoutLineItemsManager)
    {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    /**
     * Returns an array of product SKUs which were removed or have different quantity, unit or checksum.
     *
     * @param Collection<ProductLineItemInterface> $lineItems
     * @param Collection<ProductLineItemInterface> $sourceLineItems
     *
     * @return string[]
     */
    public function getProductSkusWithDifferences(Collection $lineItems, Collection $sourceLineItems): array
    {
        $changed = [];
        $lineItemsKeys = array_map([$this, 'getLineItemKey'], $lineItems->toArray());
        foreach ($sourceLineItems as $sourceLineItem) {
            $sourceLineItemKey = $this->getLineItemKey($sourceLineItem);
            if (!in_array($sourceLineItemKey, $lineItemsKeys, true)) {
                $changed[] = $sourceLineItem->getProductSku();
            }
        }

        return $changed;
    }

    /**
     * Gets checkout line items which expected to be converted into OrderLineItems filtering items
     * which could not be ordered. Array keys are preserved.
     *
     * @psalm-return ArrayCollection<int, CheckoutLineItem>
     */
    public function getCheckoutLineItems(Checkout $checkout): ArrayCollection
    {
        $orderLineItems = $this->checkoutLineItemsManager->getData($checkout);
        $orderLineItemsKeys = array_map([$this, 'getLineItemKey'], $orderLineItems->toArray());

        return $checkout->getLineItems()->filter(
            fn (CheckoutLineItem $lineItem) => \in_array($this->getLineItemKey($lineItem), $orderLineItemsKeys, true)
        );
    }

    private function getLineItemKey(ProductLineItemInterface $item): string
    {
        $key = implode(':', [$item->getProductSku(), $item->getProductUnitCode(), $item->getQuantity()]);
        if ($item instanceof ProductKitItemLineItemsAwareInterface) {
            $key .= ':' . $item->getChecksum();
        }

        return $key;
    }
}
