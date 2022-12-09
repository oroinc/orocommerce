<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider;

/**
 * Provides methods for getting shipping method object by name.
 */
class MultiShippingMethodProvider extends ChannelShippingMethodProvider
{
    public function __construct(
        $channelType,
        DoctrineHelper $doctrineHelper,
        IntegrationShippingMethodFactoryInterface $methodFactory
    ) {
        parent::__construct($channelType, $doctrineHelper, $methodFactory);
    }
}
