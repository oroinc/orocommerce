<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

// @codingStandardsIgnoreStart
/**
 * @link https://developer.paypal.com/docs/classic/payflow/integration-guide/#paypal-credit-card-transaction-request-parameters
 */
// @codingStandardsIgnoreEnd
class AuthorizationRequest extends AbstractRequest
{
    /** {@inheritdoc} */
    public function getAction()
    {
        return Option\Action::AUTHORIZATION;
    }

    /** {@inheritdoc} */
    public function configureOptions()
    {
        $this
            ->addOption(new Option\Amount())
            ->addOption(new Option\Currency());
    }
}
