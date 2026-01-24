<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;

/**
 * Connects tax resolvers to the event system.
 *
 * This connector acts as a bridge between tax resolvers and the Symfony event dispatcher.
 * It listens to {@see ResolveTaxEvent} events and delegates the tax resolution to the configured resolver.
 * If a resolver throws a {@see StopPropagationException}, the connector stops the event propagation
 * to prevent subsequent resolvers from executing.
 */
class ResolverEventConnector
{
    /** @var ResolverInterface */
    protected $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function onResolve(ResolveTaxEvent $event)
    {
        try {
            $this->resolver->resolve($event->getTaxable());
        } catch (StopPropagationException $e) {
            $event->stopPropagation();
        }
    }
}
