<?php

namespace Oro\Bundle\PaymentBundle\Method\Config\ParameterBag;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractParameterBagPaymentConfig extends ParameterBag implements PaymentConfigInterface
{
    const FIELD_LABEL = 'label';
    const FIELD_SHORT_LABEL = 'short_label';
    const FIELD_ADMIN_LABEL = 'admin_label';
    const FIELD_PAYMENT_METHOD_IDENTIFIER = 'payment_method_identifier';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->get(self::FIELD_LABEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->get(self::FIELD_SHORT_LABEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLabel()
    {
        return $this->get(self::FIELD_ADMIN_LABEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethodIdentifier()
    {
        return $this->get(self::FIELD_PAYMENT_METHOD_IDENTIFIER);
    }
}
