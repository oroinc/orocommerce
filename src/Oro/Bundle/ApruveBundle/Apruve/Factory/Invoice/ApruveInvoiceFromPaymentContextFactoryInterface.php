<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface ApruveInvoiceFromPaymentContextFactoryInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     *
     * @return ApruveInvoice
     */
    public function createFromPaymentContext(PaymentContextInterface $paymentContext);
}
