<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\EventListener;

use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener\IntegrationRemovalListenerTestCase;
use Oro\Bundle\UPSBundle\EventListener\IntegrationRemovalListener;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

class IntegrationRemovalListenerTest extends IntegrationRemovalListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createListener(
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        MethodRemovalEventDispatcherInterface $dispatcher
    ) {
        return new IntegrationRemovalListener($identifierGenerator, $dispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return ChannelType::TYPE;
    }
}
