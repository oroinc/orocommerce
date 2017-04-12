<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveOrderBuilder;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveOrderBuilderFactory implements ApruveOrderBuilderFactoryInterface
{
    /**
     * @var ApruveLineItemBuilderFactoryInterface
     */
    private $apruveLineItemBuilderFactory;

    /**
     * @param ApruveLineItemBuilderFactoryInterface $apruveLineItemBuilderFactory
     */
    public function __construct(ApruveLineItemBuilderFactoryInterface $apruveLineItemBuilderFactory)
    {
        $this->apruveLineItemBuilderFactory = $apruveLineItemBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(PaymentContextInterface $paymentContext, ApruveConfigInterface $config)
    {
        return new ApruveOrderBuilder($paymentContext, $config, $this->apruveLineItemBuilderFactory);
    }
}
