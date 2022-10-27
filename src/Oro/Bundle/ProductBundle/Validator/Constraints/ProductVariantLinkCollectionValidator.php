<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate uninitialized variant links collection.
 */
class ProductVariantLinkCollectionValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductVariantLinkCollection) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ProductVariantLinkCollection');
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array or Traversable');
        }

        // Skip validation (ValidLoadedItems validator is responsible for the initiated collection).
        if ($value instanceof AbstractLazyCollection && $value->isInitialized()) {
            return;
        }

        $validator = $this->context->getValidator()->inContext($this->context);
        if ($value instanceof PersistentCollection) {
            $value = $value->unwrap();
        }
        foreach ($value as $key => $element) {
            $validator->atPath('[' . $key . ']')->validate($element, $constraint->constraints);
        }
    }
}
