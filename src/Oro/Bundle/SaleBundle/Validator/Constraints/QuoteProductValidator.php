<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Oro\Bundle\SaleBundle\Entity;
use Oro\Bundle\SaleBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a quote product represents either a regular product or a free form product.
 */
class QuoteProductValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($quoteProduct, Constraint $constraint)
    {
        if (!$constraint instanceof Constraints\QuoteProduct) {
            throw new UnexpectedTypeException($constraint, Constraints\QuoteProduct::class);
        }

        if (!$quoteProduct instanceof Entity\QuoteProduct) {
            throw new UnexpectedTypeException($quoteProduct, Entity\QuoteProduct::class);
        }

        if ($quoteProduct->isTypeNotAvailable()) {
            $product = $quoteProduct->getProductReplacement();
            $isProductFreeForm = $quoteProduct->isProductReplacementFreeForm();
            $fieldPath = 'productReplacement';
        } else {
            $product = $quoteProduct->getProduct();
            $isProductFreeForm = $quoteProduct->isProductFreeForm();
            $fieldPath = 'product';
        }

        if (!$isProductFreeForm && null === $product) {
            $this->addViolation($fieldPath, $constraint);
        }
    }

    protected function addViolation($fieldPath, Constraints\QuoteProduct $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->atPath($fieldPath)
            ->addViolation();
    }
}
