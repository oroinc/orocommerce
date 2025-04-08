<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Oro\Bundle\SaleBundle\Entity\QuoteProduct as QuoteProductEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a quote product represents either a regular product or a free form product.
 */
class QuoteProductValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof QuoteProduct) {
            throw new UnexpectedTypeException($constraint, QuoteProduct::class);
        }

        if (!$value instanceof QuoteProductEntity) {
            throw new UnexpectedTypeException($value, QuoteProductEntity::class);
        }

        if ($value->isTypeNotAvailable()) {
            $product = $value->getProductReplacement();
            $isProductFreeForm = $value->isProductReplacementFreeForm();
            $fieldPath = 'productReplacement';
        } else {
            $product = $value->getProduct();
            $isProductFreeForm = $value->isProductFreeForm();
            $fieldPath = 'product';
        }

        if (!$isProductFreeForm && null === $product) {
            $this->context->buildViolation($constraint->message)
                ->atPath($fieldPath)
                ->addViolation();
        }
    }
}
