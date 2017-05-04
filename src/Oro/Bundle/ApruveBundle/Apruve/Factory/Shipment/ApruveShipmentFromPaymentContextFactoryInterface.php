<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface ApruveShipmentFromPaymentContextFactoryInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return ApruveShipment
     */
    public function createFromPaymentContext(PaymentContextInterface $paymentContext);
}
