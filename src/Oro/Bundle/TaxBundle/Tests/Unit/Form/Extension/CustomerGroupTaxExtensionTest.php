<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Extension\CustomerGroupTaxExtension;
use Oro\Bundle\TaxBundle\Form\Extension\CustomerTaxExtension;

class CustomerGroupTaxExtensionTest extends AbstractCustomerTaxExtensionTest
{
    /**
     * @return CustomerTaxExtension
     */
    protected function getExtension()
    {
        return new CustomerGroupTaxExtension($this->doctrineHelper, 'OroTaxBundle:CustomerTaxCode');
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(CustomerGroupType::class, $this->getExtension()->getExtendedType());
    }

    public function testOnPostSubmitNewCustomerGroup()
    {
        $taxCode = $this->createTaxCode(1);

        $customerGroup = $this->createTaxCodeTarget();
        $customerGroup->expects($this->once())->method('setTaxCode')->with($taxCode);
        $event = $this->createEvent($customerGroup);

        $this->assertTaxCodeAdd($event, $taxCode);

        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitExistingCustomerGroup()
    {
        $customerGroup = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($customerGroup);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithCustomerGroup = $this->createTaxCode(2);
        $customerGroup->method('getTaxCode')->willReturn($taxCodeWithCustomerGroup);
        $customerGroup->expects($this->once())->method('setTaxCode')->with($newTaxCode);

        $this->assertTaxCodeAdd($event, $newTaxCode);

        $this->getExtension()->onPostSubmit($event);
    }

    /**
     * @param int|null $id
     * @return CustomerGroup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createTaxCodeTarget($id = null)
    {
        $mock = $this->getMockBuilder(CustomerGroup::class)
            ->setMethods(['getTaxCode', 'setTaxCode', 'getId'])
            ->getMock();
        $mock->method('getId')->willReturn($id);
        return $mock;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepositoryFindMethod()
    {
        return 'findOneByCustomerGroup';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTestableCollection(CustomerTaxCode $customerTaxCode)
    {
        return $customerTaxCode->getCustomerGroups();
    }
}
