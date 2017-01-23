<?php

namespace Oro\Bundle\FlatRateBundle\EventListener;

use Oro\Bundle\FlatRateBundle\Integration\FlatRateChannelType;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

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
        return FlatRateChannelType::TYPE;
    }
}
