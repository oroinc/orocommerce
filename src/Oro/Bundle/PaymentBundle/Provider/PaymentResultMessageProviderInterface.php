<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Defines the contract for providing payment result error messages.
 *
 * Implementations supply error messages for payment transactions, allowing customization
 * of error messaging based on transaction details.
 */
interface PaymentResultMessageProviderInterface
{
    /**
     * @param PaymentTransaction|null $transaction
     * @return string
     */
    public function getErrorMessage(?PaymentTransaction $transaction = null);
}
