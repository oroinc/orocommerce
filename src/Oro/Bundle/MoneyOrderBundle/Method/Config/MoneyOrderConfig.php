<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config;

use Symfony\Component\HttpFoundation\ParameterBag;

class MoneyOrderConfig extends ParameterBag implements MoneyOrderConfigInterface
{
    const LABEL_KEY = 'label';
    const SHORT_LABEL_KEY = 'short_label';
    const ADMIN_LABEL_KEY = 'admin_label';
    const PAYMENT_METHOD_IDENTIFIER_KEY = 'payment_method_identifier';
    const PAY_TO_KEY = 'pay_to';
    const SEND_TO_KEY = 'send_to';

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
