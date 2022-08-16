<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\TaxBundle\Form\Extension\AbstractTaxExtension;
use Oro\Bundle\TaxBundle\Form\Extension\CustomerTaxExtension;

class CustomerTaxExtensionTest extends AbstractCustomerTaxExtensionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getExtension(): AbstractTaxExtension
    {
        return new CustomerTaxExtension();
    }

    /**
     * {@inheritDoc}
     */
    protected function createTaxCodeTarget(int $id = null): object
    {
        $entity = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
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
        $this->assertEquals([CustomerType::class], CustomerTaxExtension::getExtendedTypes());
    }

    public function testOnPostSubmitNewCustomer()
    {
        $customer = $this->createTaxCodeTarget();
        $event = $this->createEvent($customer);

        $taxCode = $this->createTaxCode(1);
        $customer->expects($this->once())
            ->method('setTaxCode')
            ->with($taxCode);
        $this->assertTaxCodeAdd($event, $taxCode);
        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitExistingCustomer()
    {
        $customer = $this->createTaxCodeTarget();
        $event = $this->createEvent($customer);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithCustomer = $this->createTaxCode(2);
        $customer->expects($this->once())
            ->method('getTaxCode')
            ->willReturn($taxCodeWithCustomer);
        $this->assertTaxCodeAdd($event, $newTaxCode);
        $customer->expects($this->once())
            ->method('setTaxCode')
            ->with($newTaxCode);
        $this->getExtension()->onPostSubmit($event);
    }
}
