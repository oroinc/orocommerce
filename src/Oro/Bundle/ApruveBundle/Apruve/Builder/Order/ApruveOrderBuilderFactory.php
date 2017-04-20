<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Order;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveOrderBuilderFactory implements ApruveOrderBuilderFactoryInterface
{
    /**
     * @var ApruveLineItemBuilderFactoryInterface
     */
    private $apruveLineItemBuilderFactory;

    /**
     * @var ShippingAmountProviderInterface
     */
    private $shippingAmountProvider;

    /**
     * @var TaxAmountProviderInterface
     */
    private $taxAmountProvider;

    /**
     * @param ApruveLineItemBuilderFactoryInterface $apruveLineItemBuilderFactory
     * @param ShippingAmountProviderInterface $shippingAmountProvider
     * @param TaxAmountProviderInterface $taxAmountProvider
     */
    public function __construct(
        ApruveLineItemBuilderFactoryInterface $apruveLineItemBuilderFactory,
        ShippingAmountProviderInterface $shippingAmountProvider,
        TaxAmountProviderInterface $taxAmountProvider
    ) {
        $this->apruveLineItemBuilderFactory = $apruveLineItemBuilderFactory;
        $this->shippingAmountProvider = $shippingAmountProvider;
        $this->taxAmountProvider = $taxAmountProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function create(PaymentContextInterface $paymentContext, ApruveConfigInterface $config)
    {
        return new ApruveOrderBuilder(
            $paymentContext,
            $config,
            $this->apruveLineItemBuilderFactory,
            $this->shippingAmountProvider,
            $this->taxAmountProvider
        );
    }
}
