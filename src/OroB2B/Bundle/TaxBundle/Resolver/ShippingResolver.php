<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class ShippingResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $event->getTaxable()->getResult()->offsetSet(Result::SHIPPING, new ResultElement());
    }
}
