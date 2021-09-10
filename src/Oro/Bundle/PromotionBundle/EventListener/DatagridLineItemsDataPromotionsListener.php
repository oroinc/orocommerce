<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener as PricingLineItemDataListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Adds line items promotions data.
 */
class DatagridLineItemsDataPromotionsListener
{
    /** @var PricingLineItemDataListener */
    private $pricingLineItemDataListener;

    /** @var PromotionExecutor */
    private $promotionExecutor;

    /** @var UserCurrencyManager */
    private $currencyManager;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var array */
    private $cache = [];

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
        $id = $lineItemsHolder->getId();
        if (!isset($this->cache[$id]) && $this->promotionExecutor->supports($lineItemsHolder)) {
            $discountContext = $this->promotionExecutor->execute($lineItemsHolder);

            $discounts = [];
            foreach ($discountContext->getLineItems() as $item) {
                $identifier = $this->getDataKey($item->getSourceLineItem());

                $discounts[$identifier] = $item->getDiscountTotal();
            }

            foreach ($lineItemsHolder->getLineItems() as $item) {
                $identifier = $this->getDataKey($item);

                if (isset($discounts[$identifier])) {
                    $this->cache[$id][$item->getId()] = $discounts[$identifier];
                }
            }
        }

        return $this->cache[$id] ?? [];
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
