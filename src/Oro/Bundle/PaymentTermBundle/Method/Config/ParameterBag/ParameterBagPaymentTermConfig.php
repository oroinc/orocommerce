<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

/**
 * Payment term configuration stored in a parameter bag.
 *
 * This configuration class extends the abstract parameter bag payment configuration
 * and implements the {@see PaymentTermConfigInterface},
 * providing a flexible way to store and access payment term configuration data.
 */
class ParameterBagPaymentTermConfig extends AbstractParameterBagPaymentConfig implements PaymentTermConfigInterface
{
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }
}
