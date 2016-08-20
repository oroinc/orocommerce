<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;

class ShippingResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        $taxable->getResult()->offsetSet(
            Result::SHIPPING,
            ResultElement::create(BigDecimal::zero(), BigDecimal::zero())
        );
    }
}
