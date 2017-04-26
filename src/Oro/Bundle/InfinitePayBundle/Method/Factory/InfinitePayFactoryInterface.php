<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Factory;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

interface InfinitePayFactoryInterface
{
    /**
     * @param InfinitePayConfigInterface $config
     *
     * @return PaymentMethodInterface
     */
    public function create(InfinitePayConfigInterface $config);
}
