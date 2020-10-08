<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\LineItemDataBuildListener as PricingLineItemDataListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;

/**
 * Adds data to the LineItemDataBuildEvent.
 */
class LineItemDataBuildListener
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

    /**
     * @param PricingLineItemDataListener $pricingLineItemDataListener
     * @param PromotionExecutor $promotionExecutor
     * @param UserCurrencyManager $currencyManager
     * @param NumberFormatter $numberFormatter
     */
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

    /**
     * @param LineItemDataBuildEvent $event
     */
    public function onLineItemData(LineItemDataBuildEvent $event): void
    {
        $this->pricingLineItemDataListener->onLineItemData($event);

        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $discountTotals = $this->getDiscountTotals(reset($lineItems)->getShoppingList());
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

            $lineItemData['initialSubtotal'] = $lineItemData['subtotal'] ?? 0;
            $lineItemData['subtotalValue'] = ($lineItemData['subtotalValue'] ?? 0) - $discountTotal;
            $lineItemData['subtotal'] = $this->numberFormatter
                ->formatCurrency($lineItemData['subtotalValue'], $currency);

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    private function getDiscountTotals(ShoppingList $shoppingList): array
    {
        $id = $shoppingList->getId();
        if (!isset($this->cache[$id]) && $this->promotionExecutor->supports($shoppingList)) {
            $discountContext = $this->promotionExecutor->execute($shoppingList);

            foreach ($discountContext->getLineItems() as $item) {
                $sourceLineItem = $item->getSourceLineItem();
                if ($sourceLineItem instanceof LineItem) {
                    $this->cache[$id][$sourceLineItem->getId()] = $item->getDiscountTotal();
                }
            }
        }

        return $this->cache[$id] ?? [];
    }
}
