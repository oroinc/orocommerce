<?php

namespace Oro\Bundle\PaymentBundle\Method\Config\ParameterBag;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides common configuration functionality for payment methods using parameter bag storage.
 *
 * This base class extends Symfony's ParameterBag to store payment method configuration parameters
 * and implements the core {@see PaymentConfigInterface} methods for accessing common configuration
 * values such as labels and identifiers. Subclasses should extend this to add payment-method-specific
 * configuration options.
 */
abstract class AbstractParameterBagPaymentConfig extends ParameterBag implements PaymentConfigInterface
{
    const FIELD_LABEL = 'label';
    const FIELD_SHORT_LABEL = 'short_label';
    const FIELD_ADMIN_LABEL = 'admin_label';
    const FIELD_PAYMENT_METHOD_IDENTIFIER = 'payment_method_identifier';

    #[\Override]
    public function getLabel()
    {
        return $this->get(self::FIELD_LABEL);
    }

    #[\Override]
    public function getShortLabel()
    {
        return $this->get(self::FIELD_SHORT_LABEL);
    }

    #[\Override]
    public function getAdminLabel()
    {
        return $this->get(self::FIELD_ADMIN_LABEL);
    }

    #[\Override]
    public function getPaymentMethodIdentifier()
    {
        return $this->get(self::FIELD_PAYMENT_METHOD_IDENTIFIER);
    }
}
