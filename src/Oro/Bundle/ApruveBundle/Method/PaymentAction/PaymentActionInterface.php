<?php

namespace Oro\Bundle\ApruveBundle\Method\PaymentAction;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PaymentActionInterface
{
    /**
     * Get payment action name.
     *
     * @return string
     */
    public function getName();

    /**
     * @param ApruveConfigInterface $apruveConfig
     * @param PaymentTransaction    $paymentTransaction
     *
     * @return array
     */
    public function execute(ApruveConfigInterface $apruveConfig, PaymentTransaction $paymentTransaction);
}
