<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;

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
