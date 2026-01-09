<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

/**
 * Defines the contract for payment method configuration objects.
 *
 * Implementations provide access to payment method labels and identifiers used for
 * displaying and identifying payment methods throughout the system.
 */
interface PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getShortLabel();

    /**
     * @return string
     */
    public function getAdminLabel();

    /**
     * @return string
     */
    public function getPaymentMethodIdentifier();
}
