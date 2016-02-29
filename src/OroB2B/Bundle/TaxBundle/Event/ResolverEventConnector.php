<?php

namespace OroB2B\Bundle\TaxBundle\Event;

use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;
use OroB2B\Bundle\TaxBundle\Resolver\StopPropagationException;

class ResolverEventConnector
{
    /** @var ResolverInterface */
    protected $resolver;

    /**
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param ResolveTaxEvent $event
     */
    public function onResolve(ResolveTaxEvent $event)
    {
        try {
            $this->resolver->resolve($event->getTaxable());
        } catch (StopPropagationException $e) {
            $event->stopPropagation();
        }
    }
}
