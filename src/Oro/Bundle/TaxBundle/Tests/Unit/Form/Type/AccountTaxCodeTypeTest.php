<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Form\Type\AccountTaxCodeType;

class AccountTaxCodeTypeTest extends AbstractTaxCodeTypeTest
{
    const DATA_CLASS = 'Oro\Bundle\TaxBundle\Entity\AccountTaxCode';

    /**
     * {@inheritdoc}
     */
    protected function createTaxCodeType()
    {
        return new AccountTaxCodeType();
    }

    /**
     * {@inheritdoc}
     */
    public function testGetName()
    {
        $this->assertEquals('oro_tax_account_tax_code_type', $this->formType->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataClass()
    {
        return self::DATA_CLASS;
    }
}
