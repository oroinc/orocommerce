<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactory;

abstract class AbstractPaymentAction implements PaymentActionInterface
{
    /**
     * @var TransactionPaymentContextFactory
     */
    protected $paymentContextFactory;

    /**
     * @param TransactionPaymentContextFactory $paymentContextFactory
     */
    public function __construct(TransactionPaymentContextFactory $paymentContextFactory)
    {
        $this->paymentContextFactory = $paymentContextFactory;
    }
}
