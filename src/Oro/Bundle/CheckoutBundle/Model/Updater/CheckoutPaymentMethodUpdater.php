<?php

namespace Oro\Bundle\CheckoutBundle\Model\Updater;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class CheckoutPaymentMethodUpdater extends AbstractCheckoutUpdater
{
    const PAYMENT_METHOD_ATTRIBUTE = 'payment_method';

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(PaymentTransactionProvider $paymentTransactionProvider)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * {@inheritDoc}
     *
     * @param Order $source
     */
    public function update(WorkflowDefinition $workflow, WorkflowData $data, $source)
    {
        $paymentMethods = $this->paymentTransactionProvider->getPaymentMethods($source);
        if ($paymentMethods) {
            $data->set(self::PAYMENT_METHOD_ATTRIBUTE, reset($paymentMethods));
        }
    }
}
