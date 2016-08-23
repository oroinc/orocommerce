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
    public function testGetName()
    {
        $this->assertEquals('orob2b_tax_product_tax_code_type', $this->formType->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
