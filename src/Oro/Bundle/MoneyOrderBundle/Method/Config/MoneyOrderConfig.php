<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Stores and provides access to Money Order payment method configuration parameters.
 *
 * This class extends {@see ParameterBag} to manage Money Order-specific configuration data including
 * labels, identifiers, and payment details. It implements {@see MoneyOrderConfigInterface} to provide
 * type-safe access to configuration values through dedicated getter methods, ensuring proper
 * data retrieval and type casting for use throughout the payment system.
 */
class MoneyOrderConfig extends ParameterBag implements MoneyOrderConfigInterface
{
    public const LABEL_KEY = 'label';
    public const SHORT_LABEL_KEY = 'short_label';
    public const ADMIN_LABEL_KEY = 'admin_label';
    public const PAYMENT_METHOD_IDENTIFIER_KEY = 'payment_method_identifier';
    public const PAY_TO_KEY = 'pay_to';
    public const SEND_TO_KEY = 'send_to';

    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }

    #[\Override]
    public function getLabel()
    {
        return (string)$this->get(self::LABEL_KEY);
    }

    #[\Override]
    public function getShortLabel()
    {
        return (string)$this->get(self::SHORT_LABEL_KEY);
    }

    #[\Override]
    public function getAdminLabel()
    {
        return (string)$this->get(self::ADMIN_LABEL_KEY);
    }

    #[\Override]
    public function getPaymentMethodIdentifier()
    {
        return (string)$this->get(self::PAYMENT_METHOD_IDENTIFIER_KEY);
    }

    #[\Override]
    public function getPayTo()
    {
        return (string)$this->get(self::PAY_TO_KEY);
    }

    #[\Override]
    public function getSendTo()
    {
        return (string)$this->get(self::SEND_TO_KEY);
    }
}
