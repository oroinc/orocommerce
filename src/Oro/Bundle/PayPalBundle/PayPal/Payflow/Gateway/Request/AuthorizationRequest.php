<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\AbstractRequest;

/**
 * Authorization request implementation
 */
class AuthorizationRequest extends AbstractRequest
{
    #[\Override]
    public function getTransactionType()
    {
        return Option\Transaction::AUTHORIZATION;
    }

    #[\Override]
    public function configureRequestOptions()
    {
        $this
            ->addOption(new Option\Tender())
            ->addOption(new Option\Amount())
            ->addOption(new Option\Currency())
            ->addOption(new GatewayOption\Customer())
            ->addOption(new GatewayOption\ExpirationDate())
            ->addOption(new GatewayOption\SecureToken())
            ->addOption(new GatewayOption\Comment())
            ->addOption(new Option\BillingAddress())
            ->addOption(new Option\ShippingAddress())
            ->addOption(new Option\Company())
            ->addOption(new Option\Company())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new Option\Invoice())
            ->addOption(new GatewayOption\Purchase())
            ->addOption(new GatewayOption\PartialAuthorization())
            ->addOption(new GatewayOption\RateLookup())
            ->addOption(new Option\Verbosity())
            ->addOption(new GatewayOption\TransparentRedirect())
            ->addOption(new GatewayOption\SecureTokenIdentifier())
            ->addOption(new GatewayOption\SilentPost())
            ->addOption(new GatewayOption\CreateSecureToken())
            ->addOption(new Option\Order())
            ->addOption(new Option\ButtonSource())
            ->addOption(new Option\IPAddress());

        return $this;
    }
}
