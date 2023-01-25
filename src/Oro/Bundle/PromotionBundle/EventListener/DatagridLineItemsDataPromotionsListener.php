<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitEntitiesProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener as PricingLineItemDataListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Adds line items promotions data.
 */
class DatagridLineItemsDataPromotionsListener
{
    private PricingLineItemDataListener $pricingLineItemDataListener;
    private PromotionExecutor $promotionExecutor;
    private UserCurrencyManager $currencyManager;
    private NumberFormatter $numberFormatter;
    private SplitEntitiesProviderInterface $splitEntitiesProvider;
    private array $cache = [];

    public function __construct(
        PricingLineItemDataListener $pricingLineItemDataListener,
        PromotionExecutor $promotionExecutor,
        UserCurrencyManager $currencyManager,
        NumberFormatter $numberFormatter
    ) {
        $this->pricingLineItemDataListener = $pricingLineItemDataListener;
        $this->promotionExecutor = $promotionExecutor;
        $this->currencyManager = $currencyManager;
        $this->numberFormatter = $numberFormatter;
    }

    public function setSplitEntitiesProvider(SplitEntitiesProviderInterface $splitEntitiesProvider)
    {
        $this->splitEntitiesProvider = $splitEntitiesProvider;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $this->pricingLineItemDataListener->onLineItemData($event);

        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $entity = $this->getMainEntity($lineItems);
        if (!$entity instanceof ProductLineItemsHolderInterface) {
            return;
        }

        $discountTotals = $this->getDiscountTotals($entity);
        if (!$discountTotals) {
            return;
        }

        $currency = $this->currencyManager->getUserCurrency();

        foreach ($lineItems as $lineItem) {
            $lineItemId = $lineItem->getId();
            $discountTotal = $discountTotals[$lineItemId] ?? null;
            if (!$discountTotal) {
                continue;
            }

            $lineItemData = $event->getDataForLineItem($lineItemId);

            $lineItemData['discountValue'] = $discountTotal;
            $lineItemData['discount'] = $this->numberFormatter->formatCurrency($discountTotal, $currency);

            // Applies changes to subtotal.
            $lineItemData['initialSubtotal'] = $lineItemData['subtotal'] ?? 0;
            $lineItemData['subtotalValue'] = ($lineItemData['subtotalValue'] ?? 0) - $discountTotal;
            $lineItemData['subtotal'] = $this->numberFormatter
                ->formatCurrency($lineItemData['subtotalValue'], $currency);

            $event->addDataForLineItem($lineItemId, $lineItemData);
        }
    }

    private function getDiscountTotals(ProductLineItemsHolderInterface $lineItemsHolder): array
    {
        $id = $lineItemsHolder->getId() ?? spl_object_hash($lineItemsHolder);
        if (!isset($this->cache[$id]) && $this->promotionExecutor->supports($lineItemsHolder)) {
            // Check if create split order functionality enabled and apply discounts for each potential suborder
            // separately.
            $splitEntities = $this->splitEntitiesProvider->getSplitEntities($lineItemsHolder);

            if (!empty($splitEntities)) {
                $discounts = [];
                foreach ($splitEntities as $entity) {
                    $discount = $this->getLineItemsDiscounts($entity);
                    // array_replace is used here just only to merge arrays with the integer keys. Its behaviour does
                    // not impact the logic.
                    $discounts = array_replace($discounts, $discount);
                }

                $this->cache[$id] = $discounts;
            } else {
                $this->cache[$id] = $this->getLineItemsDiscounts($lineItemsHolder);
            }
        }

        return $this->cache[$id] ?? [];
    }

    public function getLineItemsDiscounts(ProductLineItemsHolderInterface $lineItemsHolder): array
    {
        $discountContext = $this->promotionExecutor->execute($lineItemsHolder);

        $discounts = [];
        $lineItemsDiscounts = [];

        /** @var DiscountLineItemInterface $item */
        foreach ($discountContext->getLineItems() as $item) {
            $identifier = $this->getDataKey($item->getSourceLineItem());

            $discounts[$identifier] = $item->getDiscountTotal();
        }

        foreach ($lineItemsHolder->getLineItems() as $item) {
            $identifier = $this->getDataKey($item);

            if (isset($discounts[$identifier])) {
                $lineItemsDiscounts[$item->getId()] = $discounts[$identifier];
            }
        }

        return $lineItemsDiscounts;
    }

    private function getMainEntity(array $lineItems): ?object
    {
        $entity = null;

        $lineItem = reset($lineItems);
        switch (true) {
            case $lineItem instanceof LineItem:
                $entity = $lineItem->getShoppingList();
                break;
            case $lineItem instanceof CheckoutLineItem:
                $entity = $lineItem->getCheckout();
                break;
            default:
                break;
        }

        return $entity;
    }

    private function getDataKey(ProductLineItemInterface $item): string
    {
        return implode(':', [$item->getProductSku(), $item->getProductUnitCode(), $item->getQuantity()]);
    }
}
