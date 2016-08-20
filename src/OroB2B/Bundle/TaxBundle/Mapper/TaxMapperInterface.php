<?php

namespace Oro\Bundle\TaxBundle\Mapper;

use Oro\Bundle\TaxBundle\Model\Taxable;

interface TaxMapperInterface
{
    /**
     * @param object $object
     * @return Taxable
     */
    public function map($object);

    /**
     * Return name of class which can be mapped by this mapper
     *
     * @return string
     */
    public function getProcessingClassName();
}
