<?php

namespace Oro\Bundle\ApruveBundle\Method\Factory;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

class ApruvePaymentMethodFactory implements ApruvePaymentMethodFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ApruveConfigInterface $config)
    {
        return new ApruvePaymentMethod($config);
    }
}
