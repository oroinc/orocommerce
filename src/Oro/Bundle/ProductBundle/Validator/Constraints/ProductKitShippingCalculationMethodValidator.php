<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Product class validator for validation allowed product kit shipping calculation methods
 */
class ProductKitShippingCalculationMethodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof ProductKitShippingCalculationMethod) {
            throw new UnexpectedTypeException($constraint, ProductKitShippingCalculationMethod::class);
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        $allowedMethods = $constraint->allowedShippingCalculationMethods;
        $kitShippingCalculationMethod = $value->getKitShippingCalculationMethod();

        if ($kitShippingCalculationMethod === null) {
            return;
        }

        if (!$value->isKit()) {
            $this->addViolation(
                $constraint->message,
                $value->getType(),
                $this->formatValue($value->getKitShippingCalculationMethod()),
                $this->formatValue(null)
            );
        }

        if ($value->isKit() && !in_array($kitShippingCalculationMethod, $allowedMethods, true)) {
            $this->addViolation(
                $constraint->message,
                $value->getType(),
                $this->formatValue($value->getKitShippingCalculationMethod()),
                $this->formatValues($constraint->allowedShippingCalculationMethods)
            );
        }
    }

    private function addViolation(string $message, string $type, string $method, string $allowedMethods): void
    {
        $this->context->buildViolation($message)
            ->setParameter(
                '{{ method }}',
                $method
            )
            ->setParameter('{{ type }}', $type)
            ->setParameter(
                '{{ allowed_methods }}',
                $allowedMethods
            )
            ->atPath('kitShippingCalculationMethod')
            ->setCode(ProductKitShippingCalculationMethod::METHOD_NOT_ALLOWED_ERROR)
            ->addViolation();
    }
}
