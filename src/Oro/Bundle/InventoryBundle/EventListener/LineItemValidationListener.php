<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param QuantityToOrderValidatorService $quantityValidator
     * @param TranslatorInterface $translator
     */
    public function __construct(QuantityToOrderValidatorService $quantityValidator, TranslatorInterface $translator)
    {
        $this->validatorService = $quantityValidator;
        $this->translator = $translator;
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
            $minLimit = $this->validatorService->getMinimumLimit($product);
            $maxLimit = $this->validatorService->getMaximumLimit($product);
            // trigger error messages for products
            if (0 == $maxLimit) {
                $this->addErrorToEvent($event, $product, $maxLimit, 'quantity_limit_is_zero');
                continue;
            }

            if ($this->validatorService->isHigherThanMaxLimit($maxLimit, $lineItem->getQuantity())) {
                $this->addErrorToEvent($event, $product, $maxLimit, 'quantity_over_max_limit');
            }
            if ($this->validatorService->isLowerThenMinLimit($minLimit, $lineItem->getQuantity())) {
                $this->addErrorToEvent($event, $product, $minLimit, 'quantity_below_min_limit');
            }
        }
    }

    /**
     * @param LineItemValidateEvent $event
     * @param Product $product
     * @param int $limit
     * @param string $errorSuffix
     */
    public function addErrorToEvent(LineItemValidateEvent $event, Product $product, $limit, $errorSuffix)
    {
        $event->addError(
            $product->getSku(),
            $this->translator->trans(
                'oro.inventory.product.error.' . $errorSuffix,
                [
                    '%limit%' => $limit,
                    '%sku%' => $product->getSku(),
                    '%product_name%' => $product->getName(),
                ]
            )
        );
    }
}
