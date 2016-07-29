<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\AbstractRequest;

class AuthorizationRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getTransactionType()
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
            ->addOption(new GatewayOption\Account())
            ->addOption(new GatewayOption\ExpirationDate())
            ->addOption(new GatewayOption\SecureToken())
            ->addOption(new Option\BillingAddress())
            ->addOption(new Option\ShippingAddress())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Invoice())
            ->addOption(new GatewayOption\Purchase())
            ->addOption(new GatewayOption\PartialAuthorization())
            ->addOption(new GatewayOption\RateLookup())
            ->addOption(new Option\Verbosity())
            ->addOption(new GatewayOption\TransparentRedirect())
            ->addOption(new GatewayOption\SecureTokenIdentifier())
            ->addOption(new GatewayOption\SilentPost())
            ->addOption(new GatewayOption\CreateSecureToken());

        return $this;
    }
}
