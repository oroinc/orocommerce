<?php

namespace OroB2B\Bundle\InvoiceBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class InvoicePaymentDueDate extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.invoice.validation.payment_due_date_error.label';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
