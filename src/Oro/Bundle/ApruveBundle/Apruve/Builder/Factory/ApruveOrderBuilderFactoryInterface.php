<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveOrderBuilderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface ApruveOrderBuilderFactoryInterface
{
    /**
     * @param PaymentContextInterface $paymentContext
     * @param ApruveConfigInterface $config
     *
     * @return ApruveOrderBuilderInterface
     */
    public function create(PaymentContextInterface $paymentContext, ApruveConfigInterface $config);
}
