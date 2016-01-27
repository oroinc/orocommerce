<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class ShippingResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        $taxable->getResult()->offsetSet(Result::SHIPPING, new ResultElement());
    }
}
