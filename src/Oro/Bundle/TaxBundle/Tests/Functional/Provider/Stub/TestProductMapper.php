<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Provider\Stub;

use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TestFrameworkBundle\Entity\Product as TestProduct;

class TestProductMapper implements TaxMapperInterface
{
    /**
     * {@inheritDoc}
     * @param TestProduct $object
     */
    public function map($object)
    {
        $taxable = new Taxable();
        $taxable
            ->setClassName(TestProduct::class)
            ->setIdentifier($object->getId());

        return $taxable;
    }

    /**
     * {@inheritDoc}
     */
    public function getProcessingClassName()
    {
        return TestProduct::class;
    }
}
