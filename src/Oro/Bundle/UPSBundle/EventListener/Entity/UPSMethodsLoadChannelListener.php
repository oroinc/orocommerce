<?php

namespace Oro\Bundle\UPSBundle\EventListener\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class UPSMethodsLoadChannelListener
{
    /**
     * @var ShippingMethodProviderInterface
     */
    private $methodProvider;

    /**
     * @param ShippingMethodProviderInterface $methodProvider
     */
    public function __construct(ShippingMethodProviderInterface $methodProvider)
    {
        $this->methodProvider = $methodProvider;
    }

    /**
     * @param Channel            $channel
     * @param LifecycleEventArgs $event
     */
    public function postLoad(Channel $channel, LifecycleEventArgs $event)
    {
        $this->methodProvider->getShippingMethods();
    }
}
