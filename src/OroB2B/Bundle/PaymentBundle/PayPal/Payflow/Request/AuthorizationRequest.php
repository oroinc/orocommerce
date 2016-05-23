<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class AuthorizationRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Option\Transaction::AUTHORIZATION;
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
            ->addOption(new Option\SecureToken())
            ->addOption(new Option\BillingAddress())
            ->addOption(new Option\ShippingAddress())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Invoice())
            ->addOption(new Option\Purchase())
            ->addOption(new Option\PartialAuthorization())
            ->addOption(new Option\RateLookup())
            ->addOption(new Option\Verbosity())
            ->addOption(new Option\TransparentRedirect())
            ->addOption(new Option\SecureTokenIdentifier())
            ->addOption(new Option\CreateSecureToken());

        return $this;
    }
}
