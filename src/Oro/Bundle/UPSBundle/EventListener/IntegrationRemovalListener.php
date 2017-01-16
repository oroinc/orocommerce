<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

class IntegrationRemovalListener extends AbstractIntegrationRemovalListener
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        MethodRemovalEventDispatcherInterface $dispatcher
    ) {
        parent::__construct($identifierGenerator, $dispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return ChannelType::TYPE;
    }
}
