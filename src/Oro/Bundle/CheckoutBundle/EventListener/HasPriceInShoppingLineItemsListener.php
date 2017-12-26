<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Check if shopping list has at least one price
 * in the line items
 */
class HasPriceInShoppingLineItemsListener
{
    /**
     * @var ProductPriceProvider
     */
    private $productPriceProvider;

    /**
     * @var UserCurrencyManager
     */
    private $userCurrencyManager;

    /**
     * @var PriceListRequestHandler
     */
    private $priceListRequestHandler;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param UserCurrencyManager $userCurrencyManager
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        UserCurrencyManager $userCurrencyManager,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param ExtendableConditionEvent $conditionEvent
     */
    public function onStartCheckoutConditionCheck(ExtendableConditionEvent $conditionEvent)
    {
        /** @var ActionData $context */
        $context = $conditionEvent->getContext();

        if (!$this->isApplicable($context)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->get('checkout');
        $lineItems = $checkout->getLineItems();
        $lineItemsWithNotFixedPrice = $lineItems->filter(
            function (CheckoutLineItem $lineItem) {
                return !$lineItem->isPriceFixed();
            }
        );

        if ($lineItemsWithNotFixedPrice->isEmpty()) {
            return;
        }

        if (!$this->isThereAQuantityPresent($lineItems)) {
            $conditionEvent->addError(
                'oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_quantity'
            );

            return;
        }

        if (!$this->isThereAPricePresent($lineItemsWithNotFixedPrice)) {
            $conditionEvent->addError(
                'oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_price'
            );
        }
    }

    /**
     * @param ActionData|mixed $context
     *
     * @return bool
     */
    private function isApplicable($context)
    {
        if (!$context instanceof ActionData) {
            return false;
        }

        $checkout = $context->get('checkout');

        return $checkout instanceof Checkout;
    }

    /**
     * @param Collection|CheckoutLineItem[] $lineItems
     * @return boolean
     */
    private function isThereAPricePresent(Collection $lineItems)
    {
        $productsPricesCriteria = [];

        foreach ($lineItems as $lineItem) {
            $productsPricesCriteria[] = new ProductPriceCriteria(
                $lineItem->getProduct(),
                $lineItem->getProductUnit(),
                $lineItem->getQuantity(),
                $this->userCurrencyManager->getUserCurrency()
            );
        }

        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPricesCriteria,
            $this->priceListRequestHandler->getPriceListByCustomer()
        );

        return !empty(array_filter($prices));
    }

    /**
     * @param Collection|CheckoutLineItem[] $lineItems
     * @return boolean
     */
    private function isThereAQuantityPresent(Collection $lineItems)
    {
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getQuantity()) {
                return true;
            }
        }

        return false;
    }
}
