<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class AuthorizePaymentAction extends AbstractPaymentAction
{
    const NAME = 'authorize';

    /**
     * {@inheritdoc}
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction)
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        $response = $paymentTransaction->getResponse();
        $transactionOptions[ApruvePaymentMethod::PARAM_ORDER_ID] = $response[ApruvePaymentMethod::PARAM_ORDER_ID];
        $paymentTransaction->setTransactionOptions($transactionOptions);

        // Transaction is still not finished, payment should be captured.
        $paymentTransaction->setSuccessful(false);
        // Transaction is awaiting for payment capture.
        $paymentTransaction->setActive(true);

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
