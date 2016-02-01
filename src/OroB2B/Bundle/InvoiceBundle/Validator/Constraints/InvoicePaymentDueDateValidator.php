<?php

namespace OroB2B\Bundle\InvoiceBundle\Validator\Constraints;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

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
            /** @var ExecutionContext $context */
            $context = $this->context;

            $context->buildViolation($constraint->message, [])
                ->atPath(self::VIOLATION_PATH)
                ->addViolation();
        }
    }
}
