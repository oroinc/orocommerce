<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
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

    /**
     * {@inheritdoc}
     * @param Checkout $entity
     * @return array|null
     */
    public function getCurrentState($entity): ?array
    {
        $shoppingList = $entity->getSourceEntity();
        if (!($shoppingList instanceof ShoppingList) || !$shoppingList->getLineItems()->count()) {
            return null;
        }

        $state = [];
        $shippingContext = $this->shipContextProvider->getContext($entity);
        if ($shippingContext) {
            foreach ($shippingContext->getLineItems() as $item) {
                $state[] = $this->getCompareString($item);
            }
        }

        return $state;
    }

    public function isEntitySupported($entity): bool
    {
        return $entity instanceof Checkout;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isStatesEqual($entity, $state1, $state2): bool
    {
        if (empty($state1)) {
            // Keep original behaviour when old states is empty, that means feature didn't exist.
            return true;
        }

        return $state1 === $state2;
    }

    private function getCompareString(ShippingLineItemInterface $item): string
    {
        return sprintf(
            "s%s-u%s-q%d-p%s%d-w%d%s-d%dx%dx%d%s-i%s",
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
    }

    public function getName(): string
    {
        return self::DATA_NAME;
    }
}
