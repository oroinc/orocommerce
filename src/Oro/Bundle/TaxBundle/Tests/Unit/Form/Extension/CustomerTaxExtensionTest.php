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

    public function testGetExtendedType()
    {
        $this->assertEquals(CustomerType::NAME, $this->getExtension()->getExtendedType());
    }

    public function testOnPostSubmitNewCustomer()
    {
        $this->prepareDoctrineHelper(true, true);

        $customer = $this->createTaxCodeTarget();
        $event = $this->createEvent($customer);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod());

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$customer], $taxCode->getCustomers()->toArray());
    }

    public function testOnPostSubmitExistingCustomer()
    {
        $this->prepareDoctrineHelper(true, true);

        $customer = $this->createTaxCodeTarget();
        $event = $this->createEvent($customer);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithCustomer = $this->createTaxCode(2);
        $taxCodeWithCustomer->addCustomer($customer);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod())
            ->will($this->returnValue($taxCodeWithCustomer));

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$customer], $newTaxCode->getCustomers()->toArray());
        $this->assertEquals([], $taxCodeWithCustomer->getCustomers()->toArray());
    }

    /**
     * @param int|null $id
     *
     * @return Customer
     */
    protected function createTaxCodeTarget($id = null)
    {
        return $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', ['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepositoryFindMethod()
    {
        return 'findOneByCustomer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTestableCollection(CustomerTaxCode $customerTaxCode)
    {
        return $customerTaxCode->getCustomers();
    }
}
