<?php

namespace OroB2B\Bundle\TaxBundle\Event;

use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;

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
        $this->resolver->resolve($event->getTaxable());
    }
}
