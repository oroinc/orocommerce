<?php

namespace Oro\Bundle\InfinitePayBundle\Method\View\Factory;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

/**
 * Created by PhpStorm.
 * User: dlobato
 * Date: 05-04-2017
 * Time: 10:13
 */
interface InfinitePayViewFactoryInterface
{
    /**
     * @param InfinitePayConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    public function create(InfinitePayConfigInterface $config);
}
