<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

interface AuthorizeNetPaymentMethodFactoryInterface
{
    /**
     * @param AuthorizeNetConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(AuthorizeNetConfigInterface $config);
}
