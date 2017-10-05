<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Validator\LowInventoryLineItemValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;

class LowInventoryLineItemValidationListener
{
    /**
     * @var LowInventoryLineItemValidator
     */
    protected $lowInventoryValidator;

    /**
     * @param LowInventoryLineItemValidator $lowInventoryValidator
     */
    public function __construct(LowInventoryLineItemValidator $lowInventoryValidator)
    {
        $this->lowInventoryValidator = $lowInventoryValidator;
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

            if ($lowInventoryWarning = $this->lowInventoryValidator->getLowInventoryMessage($lineItem)) {
                $event->addWarning($lineItem->getProduct()->getSku(), $lowInventoryWarning);
            }
        }
    }
}
