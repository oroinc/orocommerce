<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Model\Taxable;

interface ResolverInterface
{
    /**
     * @param Taxable $taxable
     */
    public function resolve(Taxable $taxable);
}
