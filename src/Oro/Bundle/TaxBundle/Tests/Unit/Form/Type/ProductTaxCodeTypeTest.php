<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeType;

class ProductTaxCodeTypeTest extends AbstractTaxCodeTypeTest
{
    const DATA_CLASS = 'Oro\Bundle\TaxBundle\Entity\ProductTaxCode';
    /**
     * {@inheritdoc}
     */
    protected function createTaxCodeType()
    {
        return new ProductTaxCodeType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
