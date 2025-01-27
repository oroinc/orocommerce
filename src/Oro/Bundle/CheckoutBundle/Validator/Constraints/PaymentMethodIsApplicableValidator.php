<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a selected checkout payment method is applicable.
 */
class PaymentMethodIsApplicableValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CheckoutPaymentContextProvider $paymentContextProvider,
        private readonly ActionExecutor $actionExecutor
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PaymentMethodIsApplicable) {
            throw new UnexpectedTypeException($constraint, PaymentMethodIsApplicable::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Checkout) {
            throw new UnexpectedTypeException($value, Checkout::class);
        }

        if (!$value->getPaymentMethod()) {
            return;
        }

        if (!$this->isPaymentMethodApplicable($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(PaymentMethodIsApplicable::CODE)
                ->addViolation();
        }
    }

    private function isPaymentMethodApplicable(Checkout $checkout): bool
    {
        $paymentContext = $this->paymentContextProvider->getContext($checkout);
        if (!$paymentContext) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'payment_method_applicable',
            [
                'context' => $paymentContext,
                'payment_method' => $checkout->getPaymentMethod()
            ]
        );
    }
}
