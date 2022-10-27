<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

/**
 * Adds validation errors to LineItemValidateEvent.
 */
class LineItemValidationListener
{
    /**
     * @var QuantityToOrderValidatorService
     */
    protected $validatorService;

    public function __construct(QuantityToOrderValidatorService $quantityValidator)
    {
        $this->validatorService = $quantityValidator;
    }

    public function onLineItemValidate(LineItemValidateEvent $event)
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems instanceof \Traversable) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            // skip checking if the current line item is not supported
            if (!$this->isSupported($lineItem)) {
                continue;
            }
            $product = $lineItem->getProduct();
            if (!$product instanceof Product) {
                continue;
            }

            if ($maxError = $this->validatorService->getMaximumErrorIfInvalid($product, $lineItem->getQuantity())) {
                $event->addErrorByUnit($product->getSku(), $lineItem->getProductUnitCode(), $maxError);
                continue;
            }
            if ($minError = $this->validatorService->getMinimumErrorIfInvalid($product, $lineItem->getQuantity())) {
                $event->addErrorByUnit($product->getSku(), $lineItem->getProductUnitCode(), $minError);
            }
        }
    }

    /**
     * @param mixed $lineItem
     * @return bool
     */
    protected function isSupported($lineItem)
    {
        if ($lineItem instanceof LineItem) {
            return true;
        }
        if (!$lineItem instanceof CheckoutLineItem) {
            return false;
        }

        return !$lineItem->isPriceFixed();
    }
}
