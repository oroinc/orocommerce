<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the selected payment method is applicable for a checkout.
 */
class ApplicablePaymentMethodValidator extends ConstraintValidator
{
    public function __construct(
        private CheckoutPaymentContextProvider $paymentContextProvider,
        private ApplicablePaymentMethodsProvider $paymentMethodsProvider
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ApplicablePaymentMethod) {
            throw new UnexpectedTypeException($constraint, ApplicablePaymentMethod::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Checkout) {
            throw new UnexpectedTypeException($value, Checkout::class);
        }

        $paymentMethod = $value->getPaymentMethod();
        if (null === $paymentMethod) {
            return;
        }

        if (!$this->isApplicablePaymentMethod($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(ApplicablePaymentMethod::CODE)
                ->addViolation();
        }
    }

    /**
     * Checks if the current payment method is applicable for the given checkout.
     */
    private function isApplicablePaymentMethod(Checkout $checkout): bool
    {
        $paymentContext = $this->paymentContextProvider->getContext($checkout);
        if (null === $paymentContext) {
            return false;
        }

        $paymentMethods = $this->paymentMethodsProvider->getApplicablePaymentMethods($paymentContext);

        // If there are no applicable payment methods, another validator handles this case.
        // see @OroCheckoutBundle/Validator/Constraints/HasApplicablePaymentMethods.
        if (count($paymentMethods) === 0) {
            return true;
        }

        $paymentMethod = $checkout->getPaymentMethod();

        return array_any($paymentMethods, fn ($method) => $method->getIdentifier() === $paymentMethod);
    }
}
