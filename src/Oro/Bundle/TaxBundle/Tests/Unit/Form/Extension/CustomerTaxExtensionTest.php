<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Extension\CustomerTaxExtension;

class CustomerTaxExtensionTest extends AbstractCustomerTaxExtensionTest
{
    /**
     * @return CustomerTaxExtension
     */
    protected function getExtension()
    {
        return new CustomerTaxExtension($this->doctrineHelper, 'OroTaxBundle:CustomerTaxCode');
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
        $customer->expects($this->once())->method('setTaxCode')->with($taxCode);
        $this->assertTaxCodeAdd($event, $taxCode);
        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitExistingCustomer()
    {
        $customer = $this->createTaxCodeTarget();
        $event = $this->createEvent($customer);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithCustomer = $this->createTaxCode(2);
        $customer->expects($this->once())->method('getTaxCode')->willReturn($taxCodeWithCustomer);
        $this->assertTaxCodeAdd($event, $newTaxCode);
        $customer->expects($this->once())->method('setTaxCode')->with($newTaxCode);
        $this->getExtension()->onPostSubmit($event);
    }

    /**
     * @param int|null $id
     *
     * @return Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createTaxCodeTarget($id = null)
    {
        $mock = $this->getMockBuilder(Customer::class)
        ->disableOriginalConstructor()
        ->setMethods(['getTaxCode', 'setTaxCode', 'getId'])
        ->getMock();
        $mock->method('getId')->willReturn($id);

        return $mock;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTestableCollection(CustomerTaxCode $customerTaxCode)
    {
        return $customerTaxCode->getCustomers();
    }
}
