<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Check if shopping list has at least one price
 * in the line items
 */
class HasPriceInShoppingLineItemsListener
{
    /**
     * @var ProductPriceProviderInterface
     */
    private $productPriceProvider;

    /**
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    private $scopeCriteriaRequestHandler;

    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
    }

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
        $productsPricesCriteria = $this->productPriceCriteriaFactory->createListFromProductLineItems($lineItems);

        $prices = $this->productPriceProvider
            ->getMatchedPrices($productsPricesCriteria, $this->scopeCriteriaRequestHandler->getPriceScopeCriteria());

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
