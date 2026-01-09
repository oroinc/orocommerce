<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

/**
 * Defines the contract for Money Order payment method configuration.
 *
 * This interface extends the base {@see PaymentConfigInterface} to provide Money Order-specific
 * configuration properties, including payment recipient information (pay to) and shipping
 * address details (send to). Implementations must provide these details for proper Money Order
 * payment processing and customer communication.
 */
interface MoneyOrderConfigInterface extends PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getPayTo();

    /**
     * @return string
     */
    public function getSendTo();
}
