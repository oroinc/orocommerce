<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\LowInventoryCheckoutLineItemValidator;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks for a product line item if its inventory level is running low.
 */
class IsLowInventoryLevelValidator extends ConstraintValidator
{
    private LowInventoryCheckoutLineItemValidator $lowInventoryCheckoutLineItemValidator;

    public function __construct(LowInventoryCheckoutLineItemValidator $lowInventoryCheckoutLineItemValidator)
    {
        $this->lowInventoryCheckoutLineItemValidator = $lowInventoryCheckoutLineItemValidator;
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (!$value instanceof ProductLineItemInterface) {
            throw new UnexpectedValueException($value, ProductLineItemInterface::class);
        }

        if (!$constraint instanceof IsLowInventoryLevel) {
            throw new UnexpectedTypeException($constraint, IsLowInventoryLevel::class);
        }

        $product = $value->getProduct();
        if ($product === null) {
            return;
        }

        if ($constraint->message) {
            $message = $constraint->message;
            $isRunningLow = $this->lowInventoryCheckoutLineItemValidator->isRunningLow($value);
        } else {
            $message = $this->lowInventoryCheckoutLineItemValidator->getMessageIfRunningLow($value);
            $isRunningLow = $message !== null;
        }

        if ($isRunningLow) {
            $this->context
                ->buildViolation($message)
                ->setParameter('{{ product_sku }}', $product->getSku())
                ->atPath('quantity')
                ->setCause($value)
                ->setCode(IsLowInventoryLevel::LOW_INVENTORY_LEVEL)
                ->addViolation();
        }
    }
}
