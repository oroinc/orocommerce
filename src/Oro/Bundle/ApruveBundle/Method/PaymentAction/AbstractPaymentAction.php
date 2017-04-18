<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Context\Factory\TransactionPaymentContextFactoryInterface;

abstract class AbstractPaymentAction implements PaymentActionInterface
{
    /**
     * @var TransactionPaymentContextFactoryInterface
     */
    protected $paymentContextFactory;

    /**
     * @param TransactionPaymentContextFactoryInterface $paymentContextFactory
     */
    public function __construct(TransactionPaymentContextFactoryInterface $paymentContextFactory)
    {
        $this->paymentContextFactory = $paymentContextFactory;
    }
}
