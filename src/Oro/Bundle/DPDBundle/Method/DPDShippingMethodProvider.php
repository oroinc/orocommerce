<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider;

class DPDShippingMethodProvider extends ChannelShippingMethodProvider
{
    /**
     * {@inheritDoc}
     */
    public function __construct(
        $channelType,
        DoctrineHelper $doctrineHelper,
        IntegrationShippingMethodFactoryInterface $methodFactory
    ) {
        parent::__construct($channelType, $doctrineHelper, $methodFactory);
    }
}
