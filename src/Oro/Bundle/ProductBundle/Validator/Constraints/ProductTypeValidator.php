<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that {@see Product::$type} is within the allowed types list.
 */
class ProductTypeValidator extends ConstraintValidator
{
    /**
     * @param Product|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductType) {
            throw new UnexpectedTypeException($constraint, ProductType::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        if (!in_array($value->getType(), $constraint->allowedTypes, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ type }}', $this->formatValue($value->getType()))
                ->setParameter('{{ allowed_types }}', $this->formatValues($constraint->allowedTypes))
                ->setParameter('%count%', count($constraint->allowedTypes))
                ->atPath('type')
                ->setCode(ProductType::TYPE_NOT_ALLOWED_ERROR)
                ->addViolation();
        }
    }
}
