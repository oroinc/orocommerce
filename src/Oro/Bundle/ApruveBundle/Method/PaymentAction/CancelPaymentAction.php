<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class CancelPaymentAction extends AbstractPaymentAction
{
    const NAME = 'cancel';

    /**
     * {@inheritDoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        // Stub for cancel action.
        // TODO: will be implemented in BB-8127
        $paymentTransaction->setSuccessful(false);

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
