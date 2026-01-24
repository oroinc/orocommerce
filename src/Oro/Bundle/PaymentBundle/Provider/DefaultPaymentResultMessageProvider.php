<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Provides default error messages for payment transactions.
 *
 * This provider returns a generic error message translation key for all payment failures,
 * serving as the default implementation when no custom message provider is configured.
 */
class DefaultPaymentResultMessageProvider implements PaymentResultMessageProviderInterface
{
    #[\Override]
    public function getErrorMessage(?PaymentTransaction $transaction = null)
    {
        return 'oro.payment.result.error';
    }
}
