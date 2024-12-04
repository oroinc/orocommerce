<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that selected default product variant is one of the selected product variants
 */
class DefaultProductVariantValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DefaultProductVariant) {
            throw new UnexpectedTypeException($constraint, DefaultProductVariant::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        if (!$value->isConfigurable() || null === $value->getDefaultVariant()) {
            return;
        }

        if (!$this->isDefaultVariantBelongToProductVariants($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('defaultVariant')
                ->addViolation();
        }
    }

    private function isDefaultVariantBelongToProductVariants(Product $parentProduct): bool
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('product', $parentProduct->getDefaultVariant()));

        // We cannot use database query to check if the selected default variant is one of the product variants
        // and need to get variants from the Product object
        // because product variants may be updated in the same edit as default variant
        // and won't yet be flushed to the database
        $matching = $parentProduct->getVariantLinks()->matching($criteria);

        return !$matching->isEmpty();
    }
}
