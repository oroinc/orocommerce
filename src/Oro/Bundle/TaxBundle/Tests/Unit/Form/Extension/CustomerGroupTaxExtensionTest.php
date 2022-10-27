<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\TaxBundle\Form\Extension\AbstractTaxExtension;
use Oro\Bundle\TaxBundle\Form\Extension\CustomerGroupTaxExtension;

class CustomerGroupTaxExtensionTest extends AbstractCustomerTaxExtensionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getExtension(): AbstractTaxExtension
    {
        return new CustomerGroupTaxExtension();
    }

    /**
     * {@inheritDoc}
     */
    protected function createTaxCodeTarget(int $id = null): object
    {
        $entity = $this->getMockBuilder(CustomerGroup::class)
            ->onlyMethods(['getId'])
            ->addMethods(['getTaxCode', 'setTaxCode'])
            ->getMock();
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $entity;
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([CustomerGroupType::class], CustomerGroupTaxExtension::getExtendedTypes());
    }

    public function testOnPostSubmitNewCustomerGroup()
    {
        $taxCode = $this->createTaxCode(1);

        $customerGroup = $this->createTaxCodeTarget();
        $customerGroup->expects($this->once())
            ->method('setTaxCode')
            ->with($taxCode);

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
        $customerGroup->expects($this->any())
            ->method('getTaxCode')
            ->willReturn($taxCodeWithCustomerGroup);
        $customerGroup->expects($this->once())
            ->method('setTaxCode')
            ->with($newTaxCode);

        $this->assertTaxCodeAdd($event, $newTaxCode);

        $this->getExtension()->onPostSubmit($event);
    }
}
