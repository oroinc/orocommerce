<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\Validator\UpcomingLabelCheckoutLineItemValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

/**
 * Adds validation errors to LineItemValidateEvent.
 */
class UpcomingLabelCheckoutLineItemValidationListener
{
    /** @var UpcomingLabelCheckoutLineItemValidator */
    protected $validator;

    /** @param UpcomingLabelCheckoutLineItemValidator $validator */
    public function __construct(UpcomingLabelCheckoutLineItemValidator $validator)
    {
        $this->validator = $validator;
    }

    /** @param LineItemValidateEvent $event */
    public function onLineItemValidate(LineItemValidateEvent $event)
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems instanceof \Traversable) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof CheckoutLineItem) {
                return;
            }

            if (!$lineItem->getProduct() instanceof Product) {
                continue;
            }

            $upcomingWarning = $this->validator->getMessageIfLineItemUpcoming($lineItem);
            if ($upcomingWarning) {
                $event->addWarningByUnit(
                    $lineItem->getProduct()->getSku(),
                    $lineItem->getProductUnitCode(),
                    $upcomingWarning
                );
            }
        }
    }
}
