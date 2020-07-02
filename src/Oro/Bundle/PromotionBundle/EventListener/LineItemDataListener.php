<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataEvent;

/**
 * Adds data to the LineItemDataEvent.
 */
class LineItemDataListener
{
    /** @var PromotionExecutor */
    private $promotionExecutor;

    /** @var UserCurrencyManager */
    private $currencyManager;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var array */
    private $cache = [];

    /**
     * @param PromotionExecutor $promotionExecutor
     * @param UserCurrencyManager $currencyManager
     * @param NumberFormatter $numberFormatter     
     */
    public function __construct(
        PromotionExecutor $promotionExecutor,
        UserCurrencyManager $currencyManager,
        NumberFormatter $numberFormatter
    ) {
        $this->promotionExecutor = $promotionExecutor;
        $this->currencyManager = $currencyManager;
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param LineItemDataEvent $event
     */
    public function onLineItemData(LineItemDataEvent $event): void
    {
        $currency = $this->currencyManager->getUserCurrency();

        foreach ($event->getLineItems() as $lineItem) {
            $total = $this->getDiscount($lineItem);
            if (!$total) {
                continue;
            }

            $id = $lineItem->getId();

            $event->addDataForLineItem($id, 'discountValue', $total);
            $event->addDataForLineItem($id, 'discount', $this->numberFormatter->formatCurrency($total, $currency));
        }
    }

    /**
     * @param LineItem $lineItem
     * @return float|null
     */
    private function getDiscount(LineItem $lineItem): ?float
    {
        $shoppingList = $lineItem->getShoppingList();
        if (!$shoppingList) {
            return null;
        }

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

        return $this->cache[$id][$lineItem->getId()] ?? null;
    }
}
