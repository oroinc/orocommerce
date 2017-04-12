<?php

namespace Oro\Bundle\FlatRateShippingBundle\Builder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * @deprecated since 1.2, will be removed in 1.3.
 * Use Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory instead.
 */
class FlatRateMethodFromChannelBuilder
{
    /**
     * @var IntegrationShippingMethodFactoryInterface
     */
    private $shippingMethodFactory;

    /**
     * @param IntegrationShippingMethodFactoryInterface $shippingMethodFactory
     */
    public function __construct(IntegrationShippingMethodFactoryInterface $shippingMethodFactory)
    {
        $this->shippingMethodFactory = $shippingMethodFactory;
    }

    /**
     * @param Channel $channel
     *
     * @return ShippingMethodInterface
     */
    public function build(Channel $channel)
    {
        return $this->shippingMethodFactory->create($channel);
    }
}
