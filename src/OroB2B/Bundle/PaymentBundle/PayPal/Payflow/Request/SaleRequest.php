<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class SaleRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Option\Transaction::SALE;
    }

    /** {@inheritdoc} */
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\Amount())
            ->addOption(new Option\Currency())
            ->addOption(new Option\Account())
            ->addOption(new Option\ExpirationDate())
            ->addOption(new Option\BillingAddress())
            ->addOption(new Option\ShippingAddress())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Comment())
            ->addOption(new Option\Code())
            ->addOption(new Option\IPAddress())
            ->addOption(new Option\Swipe())
            ->addOption(new Option\Invoice())
            ->addOption(new Option\Purchase())
            ->addOption(new Option\Verbosity())
            ->addOption(new Option\TransparentRedirect())
            ->addOption(new Option\SecureTokenIdentifier())
            ->addOption(new Option\CreateSecureToken())
            ->addOption(new Option\Optional())
            ->addOption(new Option\SilentPost());

        return $this;
    }
}
