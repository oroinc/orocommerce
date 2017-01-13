<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

abstract class AbstractIntegrationRemovalListener
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var MethodRemovalEventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
     * @param MethodRemovalEventDispatcherInterface $dispatcher
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        MethodRemovalEventDispatcherInterface $dispatcher
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Channel $channel
     * @param LifecycleEventArgs $args
     */
    public function preRemove(Channel $channel, LifecycleEventArgs $args)
    {
        if ($this->getType() === $channel->getType()) {
            $this->dispatcher->dispatch($this->identifierGenerator->generateIdentifier($channel));
        }
    }

    /**
     * Doctrine entity listener should be declared only once.
     * @return string
     */
    abstract protected function getType();
}
