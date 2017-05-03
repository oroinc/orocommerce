<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentActionExecutorInterface
{
    /**
     * @param PaymentActionInterface $paymentAction
     *
     * @return $this
     */
    public function addPaymentAction(PaymentActionInterface $paymentAction);

    /**
     * @param string                $action
     * @param ApruveConfigInterface $apruveConfig
     * @param PaymentTransaction    $paymentTransaction
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function execute($action, ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function supports($name);
}
