<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

/**
 * Configuration interface for payment term payment methods.
 *
 * This interface extends the base {@see PaymentConfigInterface} and defines the contract
 * for payment term configurations, allowing implementations to provide payment term specific configuration data
 * to the payment method system.
 */
interface PaymentTermConfigInterface extends PaymentConfigInterface
{
}
