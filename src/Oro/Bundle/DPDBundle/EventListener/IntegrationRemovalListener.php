<?php

namespace Oro\Bundle\DPDBundle\EventListener;

use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\ChannelType;

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
