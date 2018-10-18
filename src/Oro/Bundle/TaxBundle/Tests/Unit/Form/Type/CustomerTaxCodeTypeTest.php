<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeType;

class CustomerTaxCodeTypeTest extends AbstractTaxCodeTypeTest
{
    const DATA_CLASS = 'Oro\Bundle\TaxBundle\Entity\CustomerTaxCode';

    /**
     * {@inheritdoc}
     */
    protected function createTaxCodeType()
    {
        return new CustomerTaxCodeType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
