<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
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
            ->addOption(new Option\Tender())
            ->addOption(new Option\Amount())
            ->addOption(new Option\Currency())
            ->addOption(new GatewayOption\Account())
            ->addOption(new GatewayOption\ExpirationDate())
            ->addOption(new Option\BillingAddress())
            ->addOption(new Option\ShippingAddress())
            ->addOption(new Option\OriginalTransaction())
            ->addOption(new GatewayOption\Comment())
            ->addOption(new GatewayOption\Code())
            ->addOption(new Option\IPAddress())
            ->addOption(new GatewayOption\Swipe())
            ->addOption(new Option\Invoice())
            ->addOption(new GatewayOption\Purchase())
            ->addOption(new Option\Verbosity())
            ->addOption(new GatewayOption\TransparentRedirect())
            ->addOption(new GatewayOption\SecureTokenIdentifier())
            ->addOption(new GatewayOption\CreateSecureToken())
            ->addOption(new GatewayOption\SilentPost());

        return $this;
    }
}
