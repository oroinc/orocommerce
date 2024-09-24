<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * A checkout diff mapper that will return status of shopping list line item, and only shopping list line item,
 * like quantity, price and inventory status.
 */
class ShoppingListLineItemDiffMapper implements CheckoutStateDiffMapperInterface
{
    private const DATA_NAME = 'shopping_list_line_item';

    private CheckoutShippingContextProvider $shipContextProvider;

    public function __construct(CheckoutShippingContextProvider $shipContextProvider)
    {
        $this->shipContextProvider = $shipContextProvider;
    }

    #[\Override]
    public function getCurrentState($entity): ?array
    {
        /** @var Checkout $entity */

        $shoppingList = $entity->getSourceEntity();
        if (!($shoppingList instanceof ShoppingList) || !$shoppingList->getLineItems()->count()) {
            return null;
        }

        $state = [];
        $lineItems = $this->shipContextProvider->getContext($entity)->getLineItems();
        foreach ($lineItems as $lineItem) {
            $state[] = $this->getCompareString($lineItem);
        }

        return $state;
    }

    #[\Override]
    public function isEntitySupported($entity): bool
    {
        return $entity instanceof Checkout;
    }

    #[\Override]
    public function isStatesEqual($entity, $state1, $state2): bool
    {
        if (empty($state1)) {
            // Keep original behaviour when old states is empty, that means feature didn't exist.
            return true;
        }

        return $state1 === $state2;
    }

    private function getCompareString(ShippingLineItem $item): string
    {
        $stringToCompare = sprintf(
            's%s-u%s-q%d-p%s%d-w%d%s-d%dx%dx%d%s-i%s',
            $item->getProduct()?->getSkuUppercase(),
            $item->getProductUnitCode(),
            $item->getQuantity(),
            $item->getPrice()?->getCurrency(),
            $item->getPrice()?->getValue(),
            $item->getWeight()?->getValue(),
            $item->getWeight()?->getUnit()?->getCode(),
            $item->getDimensions()?->getValue()?->getHeight(),
            $item->getDimensions()?->getValue()?->getLength(),
            $item->getDimensions()?->getValue()?->getWidth(),
            $item->getDimensions()?->getUnit()?->getCode(),
            $item->getProduct()?->getInventoryStatus()->getId()
        );

        if (!$item->getKitItemLineItems()->isEmpty()) {
            $stringToCompare .= sprintf('-%s', $this->getKitItemLineItemsCompareString($item->getKitItemLineItems()));
        }

        return $stringToCompare;
    }

    private function getKitItemLineItemsCompareString(Collection $kitItemLineItems): string
    {
        $kitItemLineItemsKey = [];
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $kitItemLineItemsKey[] = $this->getKitItemLineItemCompareString($kitItemLineItem);
        }

        return implode('-', $kitItemLineItemsKey);
    }

    private function getKitItemLineItemCompareString(ShippingKitItemLineItem $kitItemLineItem): string
    {
        return sprintf(
            'kilis%s-kiliu%s-kiliq%d-kilip%s%d',
            $kitItemLineItem->getProduct()?->getSkuUppercase(),
            $kitItemLineItem->getProductUnitCode(),
            $kitItemLineItem->getQuantity(),
            $kitItemLineItem->getPrice()?->getCurrency(),
            $kitItemLineItem->getPrice()?->getValue()
        );
    }

    #[\Override]
    public function getName(): string
    {
        return self::DATA_NAME;
    }
}
