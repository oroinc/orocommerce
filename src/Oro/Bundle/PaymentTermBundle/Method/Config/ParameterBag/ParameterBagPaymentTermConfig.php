<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

class ParameterBagPaymentTermConfig extends AbstractParameterBagPaymentConfig implements PaymentTermConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }
}
