<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that quantity is within maximum and minimum quantity to order limits.
 */
class QuantityToOrderValidator extends ConstraintValidator
{
    private QuantityToOrderValidatorService $quantityToOrderValidator;

    public function __construct(QuantityToOrderValidatorService $quantityToOrderValidatorService)
    {
        $this->quantityToOrderValidator = $quantityToOrderValidatorService;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof ProductHolderInterface || !$value instanceof QuantityAwareInterface) {
            throw new UnexpectedValueException(
                $value,
                sprintf('%s & %s', ProductHolderInterface::class, QuantityAwareInterface::class)
            );
        }

        if (!$constraint instanceof QuantityToOrder) {
            throw new UnexpectedTypeException($constraint, QuantityToOrder::class);
        }

        $product = $value->getProduct();
        if (!$product instanceof Product) {
            return;
        }

        $minimumError = $this->quantityToOrderValidator->getMinimumErrorIfInvalid($product, $value->getQuantity());
        if ($minimumError !== false) {
            $this->context
                ->buildViolation($minimumError)
                ->setCode($constraint::LESS_THAN_MIN_LIMIT)
                ->atPath('quantity')
                ->addViolation();
        }

        $maximumError = $this->quantityToOrderValidator->getMaximumErrorIfInvalid($product, $value->getQuantity());
        if ($maximumError !== false) {
            $this->context
                ->buildViolation($maximumError)
                ->setCode($constraint::GREATER_THAN_MAX_LIMIT)
                ->atPath('quantity')
                ->addViolation();
        }
    }
}
