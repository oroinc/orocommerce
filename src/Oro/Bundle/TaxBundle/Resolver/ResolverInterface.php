<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Abstraction for tax resolvers.
 */
interface ResolverInterface
{
    public function resolve(Taxable $taxable): void;
}
