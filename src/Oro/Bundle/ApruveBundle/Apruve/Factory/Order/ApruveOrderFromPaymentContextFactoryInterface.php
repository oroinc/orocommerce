<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface ApruveOrderFromPaymentContextFactoryInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     * @param ApruveConfigInterface   $apruveConfig
     *
     * @return ApruveOrder
     */
    public function createFromPaymentContext(
        PaymentContextInterface $paymentContext,
        ApruveConfigInterface $apruveConfig
    );
}
