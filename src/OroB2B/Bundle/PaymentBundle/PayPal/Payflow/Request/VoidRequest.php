<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class VoidRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Option\Transaction::VOID;
    }

    /** {@inheritdoc} */
    public function configureOptions()
    {
        $this
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity());
    }
}
