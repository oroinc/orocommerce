<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class AuthorizePaymentAction extends AbstractPaymentAction
{
    const NAME = PaymentMethodInterface::AUTHORIZE;

    /**
     * {@inheritDoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $response = $paymentTransaction->getResponse();

        // AUTHORIZE transaction holds ApruveOrderId in reference property.
        $paymentTransaction->setReference($response[ApruvePaymentMethod::PARAM_ORDER_ID]);

        $paymentTransaction->setAction(PaymentMethodInterface::AUTHORIZE);

        $paymentTransaction->setSuccessful(true);

        // Transaction is awaiting for payment capture.
        $paymentTransaction->setActive(true);

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
