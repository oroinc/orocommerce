<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that line item has price.
 */
class LineItemHasPriceValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ProductPriceProviderInterface $productPriceProvider,
        private readonly ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        private readonly ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof LineItemHasPrice) {
            throw new UnexpectedTypeException($constraint, LineItemHasPrice::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ProductLineItemInterface) {
            throw new UnexpectedValueException($value, ProductLineItemInterface::class);
        }

        if ($value->getProduct()?->isConfigurable()) {
            return;
        }

        if (!$this->hasPrice($value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function hasPrice(ProductLineItemInterface $lineItem): bool
    {
        if ($lineItem instanceof CheckoutLineItem && $lineItem->isPriceFixed()) {
            $price = $lineItem->getPrice();
            return $price && $price->getCurrency() !== null;
        }

        $productsPricesCriteria = $this->productPriceCriteriaFactory->createListFromProductLineItems([$lineItem]);
        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPricesCriteria,
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
        );

        return !empty(array_filter($prices));
    }
}
