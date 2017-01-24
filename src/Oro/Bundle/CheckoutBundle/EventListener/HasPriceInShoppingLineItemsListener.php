<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
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
        if (!$checkout->getLineItems()->count()) {
            return;
        }

        if (!$this->isThereAPricePresent($checkout->getLineItems())) {
            $conditionEvent->addError(
                'oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_price'
            );
        }
    }

    private function isApplicable($context)
    {
        return ($context instanceof ActionData && $context->get('checkout') instanceof Checkout);
    }

    /**
     * @param LineItem[]|ArrayCollection $lineItems
     * @return boolean
     */
    private function isThereAPricePresent($lineItems)
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
}
