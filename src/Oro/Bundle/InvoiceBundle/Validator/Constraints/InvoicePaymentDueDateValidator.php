<?php

namespace Oro\Bundle\InvoiceBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\InvoiceBundle\Entity\Invoice;

class InvoicePaymentDueDateValidator extends ConstraintValidator
{
    const VIOLATION_PATH = 'paymentDueDate';

    /**
     * {@inheritdoc}
     * @param Invoice $value
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Invoice) {
            return;
        }

        if ($value->getPaymentDueDate() < $value->getInvoiceDate()) {
            /** @var ExecutionContextInterface $context */
            $context = $this->context;

            $context->buildViolation($constraint->message, [])
                ->atPath(self::VIOLATION_PATH)
                ->addViolation();
        }
    }
}
