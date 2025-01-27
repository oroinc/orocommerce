<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a checkout has applicable shipping rules to be used.
 */
class HasApplicableShippingRulesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ShippingMethodActionsInterface $shippingMethodActions
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasApplicableShippingRules) {
            throw new UnexpectedTypeException($constraint, HasApplicableShippingRules::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Checkout) {
            throw new UnexpectedTypeException($value, Checkout::class);
        }

        $errors = new ArrayCollection();
        if ($this->shippingMethodActions->hasApplicableShippingRules($value, $errors)) {
            return;
        }

        if ($errors->isEmpty()) {
            $errors->add(['message' => $constraint->message]);
        }
        foreach ($errors as $error) {
            $this->context->buildViolation($error['message'])
                ->setParameters($error['parameters'] ?? [])
                ->setCode(HasApplicableShippingRules::CODE)
                ->addViolation();
        }
    }
}
