<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;

class CustomerAddressResolver implements ResolverInterface
{
    /** @var CustomerAddressItemResolver */
    protected $itemResolver;

    /**
     * @param CustomerAddressItemResolver $itemResolver
     */
    public function __construct(CustomerAddressItemResolver $itemResolver)
    {
        $this->itemResolver = $itemResolver;
    }

    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();
        if (!$taxable->getItems()->count()) {
            return;
        }

        foreach ($taxable->getItems() as $taxableItem) {
            /** @todo: get rid of event in interface */
            $this->itemResolver->resolve(new ResolveTaxEvent($taxableItem));
        }
    }
}
