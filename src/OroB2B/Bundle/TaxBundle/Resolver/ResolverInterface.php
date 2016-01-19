<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;

interface ResolverInterface
{
    /**
     * @param ResolveTaxEvent $event
     */
    public function resolve(ResolveTaxEvent $event);
}
