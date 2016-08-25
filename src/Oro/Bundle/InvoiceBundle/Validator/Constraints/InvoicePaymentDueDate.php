<?php

namespace Oro\Bundle\InvoiceBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class InvoicePaymentDueDate extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.invoice.validation.payment_due_date_error.label';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
