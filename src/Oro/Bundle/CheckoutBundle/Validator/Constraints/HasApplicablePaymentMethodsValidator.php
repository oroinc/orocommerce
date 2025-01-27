<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a checkout has applicable payment methods to be used.
 */
class HasApplicablePaymentMethodsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CheckoutPaymentContextProvider $paymentContextProvider,
        private readonly ActionExecutor $actionExecutor
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasApplicablePaymentMethods) {
            throw new UnexpectedTypeException($constraint, HasApplicablePaymentMethods::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Checkout) {
            throw new UnexpectedTypeException($value, Checkout::class);
        }

        if (!$this->hasApplicablePaymentMethods($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(HasApplicablePaymentMethods::CODE)
                ->addViolation();
        }
    }

    private function hasApplicablePaymentMethods(Checkout $checkout): bool
    {
        $paymentContext = $this->paymentContextProvider->getContext($checkout);
        if (!$paymentContext) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'has_applicable_payment_methods',
            ['context' => $paymentContext]
        );
    }
}
