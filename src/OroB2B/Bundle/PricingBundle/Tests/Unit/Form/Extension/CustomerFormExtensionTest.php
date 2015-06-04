<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Extension\CustomerFormExtension;

class CustomerFormExtensionTest extends AbstractPriceListExtensionTest
{
    /**
     * @return CustomerFormExtension
     */
    protected function getExtension()
    {
        return new CustomerFormExtension($this->registry);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetDataProvider()
    {
        return [
            [null, false],
            [new Customer(), false],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1), true],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1), true],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1), true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmitDataProvider()
    {
        return [
            [null, false],
            [new Customer(), false],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1), true],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1), false, false],
            [$this->getEntity('OroB2B\Bundle\CustomerBundle\Entity\Customer', 1), true, true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getGetterMethodName()
    {
        return 'getPriceListByCustomer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetterMethodName()
    {
        return 'setPriceListToCustomer';
    }

    /**
     * {@inheritdoc}
     */
    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->getExtension()->getExtendedType());
        $this->assertEquals('orob2b_customer_type', $this->getExtension()->getExtendedType());
    }
}
