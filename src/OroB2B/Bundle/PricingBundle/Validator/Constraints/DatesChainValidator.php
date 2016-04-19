<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DatesChainValidator extends ConstraintValidator
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
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
                    'invalid' => $label,
                    'comparedWith' => $previousLabel
                ])
                    ->atPath($property)
                    ->addViolation();
            }

            $previous = $current;
            $previousLabel = $label;
        }
    }
}
