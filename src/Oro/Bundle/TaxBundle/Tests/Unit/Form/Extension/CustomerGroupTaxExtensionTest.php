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
        $this->assertEquals(CustomerGroupType::NAME, $this->getExtension()->getExtendedType());
    }

    public function testOnPostSubmitNewCustomerGroup()
    {
        $this->prepareDoctrineHelper(true, true);

        $customer = $this->createTaxCodeTarget();
        $event = $this->createEvent($customer);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod());

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$customer], $taxCode->getCustomerGroups()->toArray());
    }

    public function testOnPostSubmitExistingCustomerGroup()
    {
        $this->prepareDoctrineHelper(true, true);

        $customerGroup = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($customerGroup);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithCustomerGroup = $this->createTaxCode(2);
        $taxCodeWithCustomerGroup->addCustomerGroup($customerGroup);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod())
            ->will($this->returnValue($taxCodeWithCustomerGroup));

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$customerGroup], $newTaxCode->getCustomerGroups()->toArray());
        $this->assertEquals([], $taxCodeWithCustomerGroup->getCustomerGroups()->toArray());
    }

    /**
     * @param int|null $id
     * @return CustomerGroup
     */
    protected function createTaxCodeTarget($id = null)
    {
        return $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerGroup', ['id' => $id]);
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
