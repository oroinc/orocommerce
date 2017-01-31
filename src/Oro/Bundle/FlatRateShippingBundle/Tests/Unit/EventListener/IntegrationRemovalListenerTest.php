<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FlatRateShippingBundle\EventListener\IntegrationRemovalListener;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener\IntegrationRemovalListenerTestCase;

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
        return FlatRateChannelType::TYPE;
    }
}
