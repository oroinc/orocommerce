<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Model\Taxable;

interface ResolverInterface
{
    public function resolve(Taxable $taxable);
}
