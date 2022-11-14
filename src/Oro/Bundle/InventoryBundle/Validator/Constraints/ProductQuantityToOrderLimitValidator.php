<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Model\Inventory;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator checks whether the product's maximum inventory quantity is higher or equal to
 * the product's minimum inventory quantity.
 */
class ProductQuantityToOrderLimitValidator extends ConstraintValidator
{
    private QuantityToOrderValidatorService $validatorService;

    public function __construct(QuantityToOrderValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductQuantityToOrderLimit) {
            throw new UnexpectedTypeException($constraint, ProductQuantityToOrderLimit::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Product) {
            throw new UnexpectedTypeException($value, Product::class);
        }

        if ($value->getId() && $this->validatorService->isMaxLimitLowerThenMinLimit($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath(Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER)
                ->addViolation();
        }
    }
}
