<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Model\Taxable;

interface ResolverInterface
{
    /**
     * @param Taxable $taxable
     */
    public function resolve(Taxable $taxable);
}
