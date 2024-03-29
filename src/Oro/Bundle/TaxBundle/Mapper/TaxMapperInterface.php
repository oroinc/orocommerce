<?php

namespace Oro\Bundle\TaxBundle\Mapper;

use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Represents a service that creates {@see \Oro\Bundle\TaxBundle\Model\Taxable} object
 * and fills it with data from a given object.
 */
interface TaxMapperInterface
{
    public function map(object $object): Taxable;
}
