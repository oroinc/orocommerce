<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
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
     * Returns an array of product SKUs which were removed or have different quantity or unit.
     *
     * @param Collection<ProductLineItemInterface> $lineItems
     * @param Collection<ProductLineItemInterface> $sourceLineItems
     *
     * @return string[]
     */
    public function getProductSkusWithDifferences(Collection $lineItems, Collection $sourceLineItems): array
    {
        $changed = [];
        foreach ($sourceLineItems as $sourceLineItem) {
            $found = false;
            foreach ($lineItems as $lineItem) {
                if ($sourceLineItem->getProductSku() === $lineItem->getProductSku()
                    && $sourceLineItem->getProductUnitCode() === $lineItem->getProductUnitCode()
                    && $sourceLineItem->getQuantity() === $lineItem->getQuantity()
                ) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $changed[] = $sourceLineItem->getProductSku();
            }
        }

        return $changed;
    }

    /**
     * Gets checkout line items which expected to be converted into OrderLineItems filtering items
     * which could not be ordered.
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
        return implode(':', [$item->getProductSku(), $item->getProductUnitCode(), $item->getQuantity()]);
    }
}
