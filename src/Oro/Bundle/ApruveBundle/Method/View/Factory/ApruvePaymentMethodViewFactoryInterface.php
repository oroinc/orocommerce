<?php

namespace Oro\Bundle\ApruveBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

interface ApruvePaymentMethodViewFactoryInterface
{
    /**
     * @param ApruveConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    public function create(ApruveConfigInterface $config);
}
