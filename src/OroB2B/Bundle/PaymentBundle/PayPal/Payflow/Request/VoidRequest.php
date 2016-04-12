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
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Verbosity());

        return $this;
    }
}
