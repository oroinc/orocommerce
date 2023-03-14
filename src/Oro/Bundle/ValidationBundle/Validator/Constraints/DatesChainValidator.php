<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Dates chain validator.
 */
class DatesChainValidator extends ConstraintValidator
{
    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object|array $value
     * @param DatesChain|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $previous = null;
        $previousLabel = null;
        foreach ($constraint->chain as $property => $label) {
            $current = $this->propertyAccessor->getValue($value, $property);
            if (!$current instanceof \DateTime) {
                continue;
            }

            if ($current < $previous) {
                $this->context->buildViolation($constraint->message, [
                    'later' => $label,
                    'earlier' => $previousLabel
                ])
                    ->atPath($property)
                    ->addViolation();
            }

            $previous = $current;
            $previousLabel = $label;
        }
    }
}
