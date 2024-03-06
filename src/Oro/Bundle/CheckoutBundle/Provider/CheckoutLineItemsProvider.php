<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

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
     * Gets an array of product SKUs which were removed or have different quantity, unit or checksum.
     *
     * @param Collection<int, ProductLineItemInterface> $lineItems
     * @param Collection<int, ProductLineItemInterface> $sourceLineItems
     *
     * @return string[]
     */
    public function getProductSkusWithDifferences(Collection $lineItems, Collection $sourceLineItems): array
    {
        $changed = [];
        $lineItemKeyMap = $this->getLineItemKeyMap($lineItems);
        foreach ($sourceLineItems as $sourceLineItem) {
            if (!isset($lineItemKeyMap[$this->getLineItemKey($sourceLineItem)])) {
                $changed[] = $sourceLineItem->getProductSku();
            }
        }

        return $changed;
    }

    /**
     * Gets checkout line items which expected to be converted into OrderLineItems filtering items
     * which could not be ordered. Array keys are preserved.
     *
     * @psalm-return Collection<int, CheckoutLineItem>
     */
    public function getCheckoutLineItems(Checkout $checkout): Collection
    {
        $orderLineItemKeyMap = $this->getLineItemKeyMap($this->checkoutLineItemsManager->getData($checkout));

        return $checkout->getLineItems()->filter(
            fn (CheckoutLineItem $lineItem) => isset($orderLineItemKeyMap[$this->getLineItemKey($lineItem)])
        );
    }

    /**
     * @param Collection<int, ProductLineItemInterface> $lineItems
     *
     * @return array [line item key => true, ...]
     */
    private function getLineItemKeyMap(Collection $lineItems): array
    {
        $result = [];
        foreach ($lineItems as $lineItem) {
            $result[$this->getLineItemKey($lineItem)] = true;
        }

        return $result;
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
