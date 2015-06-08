<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Extension\CustomerGroupFormExtension;

class CustomerGroupFormExtensionTest extends AbstractPriceListExtensionTest
{
    /**
     * @return CustomerGroupFormExtension
     */
    protected function getExtension()
    {
        return new CustomerGroupFormExtension($this->registry);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetDataProvider()
    {
        return [
            [null, false],
            [new CustomerGroup(), false],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 1), true],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 1), true],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 1), true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmitDataProvider()
    {
        return [
            [null, false],
            [new CustomerGroup(), false],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 1), true],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 1), false, false],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup', 1), true, true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getGetterMethodName()
    {
        return 'getPriceListByCustomerGroup';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetterMethodName()
    {
        return 'setPriceListToCustomerGroup';
    }

    /**
     * {@inheritdoc}
     */
    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->getExtension()->getExtendedType());
        $this->assertEquals('orob2b_customer_group_type', $this->getExtension()->getExtendedType());
    }
}
