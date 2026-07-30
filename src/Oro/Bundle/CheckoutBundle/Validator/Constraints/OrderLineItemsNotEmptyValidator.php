<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmptyInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates order line items created from a checkout for 2 cases:
 * 1) if order line items (at least one) can be added to the checkout;
 * 2) if there are no order line items can be added to order, then checks if order line items (at least one)
 *    can be added to RFP
 */
class OrderLineItemsNotEmptyValidator extends ConstraintValidator
{
    public function __construct(
        private readonly OrderLineItemsNotEmptyInterface $orderLineItemsNotEmpty
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof OrderLineItemsNotEmpty) {
            throw new UnexpectedTypeException($constraint, OrderLineItemsNotEmpty::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Checkout) {
            throw new UnexpectedTypeException($value, Checkout::class);
        }

        $result = $this->orderLineItemsNotEmpty->execute($value);

        if (!empty($result['orderLineItemsNotEmpty'])) {
            return;
        }

        $this->addViolations($constraint, $result);
    }

    private function addViolations(Constraint $constraint, array $result): void
    {
        $reasons = $result['orderLineItemsSkippedReasons'] ?? [];

        if (\in_array(CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH, $reasons, true)) {
            $this->context->buildViolation($constraint->notEmptyDifferentCurrencyMessage)
                ->setCode(OrderLineItemsNotEmpty::DIFFERENT_CURRENCY_CODE)
                ->addViolation();
        }

        $noPrice = \in_array(CheckoutLineItemsManager::REASON_NO_PRICE, $reasons, true)
            || \in_array(CheckoutLineItemsManager::REASON_UNSUPPORTED_STATUS, $reasons, true);

        if ($noPrice || empty($reasons)) {
            if (empty($result['orderLineItemsNotEmptyForRfp'])) {
                $this->context->buildViolation($constraint->notEmptyForRfpMessage)
                    ->setCode(OrderLineItemsNotEmpty::EMPTY_FOR_RFP_CODE)
                    ->addViolation();
            } else {
                $this->context->buildViolation($constraint->notEmptyMessage)
                    ->setCode(OrderLineItemsNotEmpty::EMPTY_CODE)
                    ->addViolation();
            }
        }
    }
}
