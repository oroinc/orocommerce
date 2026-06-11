<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the shopping list total satisfies the configured minimum and maximum order amount.
 */
class OrderAmountLimitsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly OrderLimitProviderInterface $orderLimitProvider,
        private readonly OrderLimitFormattedProviderInterface $orderLimitFormattedProvider
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof OrderAmountLimits) {
            throw new UnexpectedTypeException($constraint, OrderAmountLimits::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ShoppingList) {
            throw new UnexpectedTypeException($value, ShoppingList::class);
        }

        if (!$this->orderLimitProvider->isMinimumOrderAmountMet($value)) {
            $this->addViolation(
                $constraint->minimumMessage,
                $this->orderLimitFormattedProvider->getMinimumOrderAmountFormatted(),
                $this->orderLimitFormattedProvider->getMinimumOrderAmountDifferenceFormatted($value),
                OrderAmountLimits::MINIMUM_NOT_MET_CODE
            );

            // Stop after the first violation: minimum and maximum can both fail simultaneously only when the
            // system configuration itself is invalid (max < min); stacking two flash messages in that case is
            // noisy and confusing. Surface the more restrictive (minimum) failure consistently instead.
            return;
        }

        if (!$this->orderLimitProvider->isMaximumOrderAmountMet($value)) {
            $this->addViolation(
                $constraint->maximumMessage,
                $this->orderLimitFormattedProvider->getMaximumOrderAmountFormatted(),
                $this->orderLimitFormattedProvider->getMaximumOrderAmountDifferenceFormatted($value),
                OrderAmountLimits::MAXIMUM_NOT_MET_CODE
            );
        }
    }

    private function addViolation(string $message, string $amount, string $difference, string $code): void
    {
        $this->context->buildViolation($message)
            ->setParameter('%amount%', $amount)
            ->setParameter('%difference%', $difference)
            ->setCode($code)
            ->addViolation();
    }
}
