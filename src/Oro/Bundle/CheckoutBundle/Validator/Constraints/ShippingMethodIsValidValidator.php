<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a selected checkout shipping method is valid.
 */
class ShippingMethodIsValidValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ShippingMethodProviderInterface $shippingMethodProvider
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ShippingMethodIsValid) {
            throw new UnexpectedTypeException($constraint, ShippingMethodIsValid::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        $shippingMethodId = $value->getShippingMethod();
        $shippingMethodType = $value->getShippingMethodType();
        if (null !== $shippingMethodId) {
            $shippingMethod = $this->shippingMethodProvider->getShippingMethod($shippingMethodId);
            if (null === $shippingMethod) {
                // shipping method should exist for a given shipping method id
                $this->context->buildViolation($constraint->shippingMethodMessage)
                    ->setCode(ShippingMethodIsValid::CODE)
                    ->addViolation();
            } elseif (!$this->isKnownShippingMethodType($shippingMethod, $shippingMethodType)) {
                // shipping method type must be associated with shipping method when shipping method has types
                $this->context->buildViolation($constraint->shippingMethodTypeMessage)
                    ->setCode(ShippingMethodIsValid::CODE)
                    ->addViolation();
            }
        } elseif (null !== $shippingMethodType) {
            // shipping method type can't be set without shipping method
            $this->context->buildViolation($constraint->shippingMethodTypeMessage)
                ->setCode(ShippingMethodIsValid::CODE)
                ->addViolation();
        }
    }

    private function isKnownShippingMethodType(
        ShippingMethodInterface $shippingMethod,
        ?string $shippingMethodType
    ): bool {
        if (!$shippingMethodType) {
            return false;
        }

        $knownTypes = $shippingMethod->getTypes();
        if (!$knownTypes) {
            return false;
        }

        return null !== $shippingMethod->getType($shippingMethodType);
    }
}
