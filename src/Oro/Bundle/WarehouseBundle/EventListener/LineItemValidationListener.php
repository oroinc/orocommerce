<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WarehouseBundle\Validator\QuantityToOrderValidator;

class LineItemValidationListener
{
    /**
     * @var QuantityToOrderValidator
     */
    protected $quantityValidator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * LineItemValidationListener constructor.
     *
     * @param QuantityToOrderValidator $quantityValidator
     * @param TranslatorInterface $translator
     */
    public function __construct(QuantityToOrderValidator $quantityValidator, TranslatorInterface $translator)
    {
        $this->quantityValidator = $quantityValidator;
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
            $minLimit = $this->quantityValidator->getMinimumLimit($product);
            $maxLimit = $this->quantityValidator->getMaximumLimit($product);

            // trigger error messages for products
            if ($this->quantityValidator->isHigherThanMaxLimit($maxLimit, $lineItem->getQuantity())) {
                $this->addErrorToEvent($event, $product, $maxLimit, 'quantity_over_max_limit');
            }
            if ($this->quantityValidator->isLowerThenMinLimit($minLimit, $lineItem->getQuantity())) {
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
                'oro.product.error.' . $errorSuffix,
                [
                    '%limit%' => $limit,
                    '%sku%' => $product->getSku(),
                    '%product_name%' => $product->getName(),
                ]
            )
        );
    }
}
