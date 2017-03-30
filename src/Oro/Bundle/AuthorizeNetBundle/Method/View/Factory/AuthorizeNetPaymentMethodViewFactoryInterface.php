<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\View\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

interface AuthorizeNetPaymentMethodViewFactoryInterface
{
    /**
     * @param AuthorizeNetConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(AuthorizeNetConfigInterface $config);
}
