<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\AbstractRequest;

class SaleRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getTransactionType()
    {
        return Option\Transaction::SALE;
    }

    /** {@inheritdoc} */
    public function configureRequestOptions()
    {
        $this
            ->addOption(new ECOption\Action())
            ->addOption(new ECOption\Tender())
            ->addOption(new ECOption\Amount())
            ->addOption(new ECOption\Token())
            ->addOption(new ECOption\ReturnUrl())
            ->addOption(new ECOption\CancelUrl())
            ->addOption(new ECOption\Currency())
            ->addOption(new ECOption\Invoice())
            ->addOption(new ECOption\LineItems())
            ->addOption(new ECOption\Payer())
            ->addOption(new ECOption\PaymentType())
            ->addOption(new ECOption\ShippingAddress())
            ->addOption(new ECOption\ShippingAddressOverride())
            ->addOption(new Option\Verbosity());

        return $this;
    }
}
