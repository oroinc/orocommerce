<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class LineItemValidationListener
{
    /**
     * @var QuantityToOrderValidatorService
     */
    protected $validatorService;

    /**
     * @param QuantityToOrderValidatorService $quantityValidator
     */
    public function __construct(QuantityToOrderValidatorService $quantityValidator)
    {
        $this->validatorService = $quantityValidator;
    }

    /**
     * @param LineItemValidateEvent $event
     */
    public function onLineItemValidate(LineItemValidateEvent $event)
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems instanceof \Traversable) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            // stop checking if list item is not LineItem
            if (!$lineItem instanceof LineItem) {
                return;
            }
            if (!$lineItem->getProduct() instanceof Product) {
                continue;
            }

            $product = $lineItem->getProduct();
            if ($maxError = $this->validatorService->getMaximumErrorIfInvalid($product, $lineItem->getQuantity())) {
                $event->addError($product->getSku(), $maxError);
                continue;
            }
            if ($minError = $this->validatorService->getMinimumErrorIfInvalid($product, $lineItem->getQuantity())) {
                $event->addError($product->getSku(), $minError);
            }
        }
    }
}
