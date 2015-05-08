<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Model;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel;
use OroB2B\Bundle\CustomerBundle\Model\CustomerModel;

class CustomerGroupModelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCustomers()
    {
        $customerGroup = new CustomerGroup();
        $customer1 = new Customer();
        $customer1->setName('customer1');
        $customer2 = new Customer();
        $customer2->setName('customer2');
        $customerGroup->addCustomer($customer1);
        $customerGroup->addCustomer($customer2);

        foreach ($this->getModel($customerGroup)->getCustomers() as $key => $customerModel) {
            $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Model\CustomerModel', $customerModel);

            $this->assertEquals(
                [$customerGroup->getCustomers()->get($key)],
                $customerModel->getEntities()
            );
        }
    }

    public function testGetCustomersEmpty()
    {
        $customerGroup = new CustomerGroup();

        $model = $this->getModel($customerGroup);

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $model->getCustomers());
        $this->assertTrue($model->getCustomers()->isEmpty());
    }

    public function testGetName()
    {
        $name = 'customerGroup';
        $group = new CustomerGroup();
        $group->setName($name);
        $model = $this->getModel($group);

        $this->assertInternalType('string', $model->getName());
        $this->assertEquals($name, $model->getName());

        $alteredName = 'alteredCustomerGroup';
        $model->setName($alteredName);

        $this->assertEquals($alteredName, $group->getName());
        $this->assertEquals($alteredName, $model->getName());
    }

    public function testGetNameEmpty()
    {
        $group = new CustomerGroup();
        $model = $this->getModel($group);

        $this->assertNull($model->getName());
    }

    public function testGetModelName()
    {
        $customerGroup = new CustomerGroup();

        $model = $this->getModel($customerGroup);

        $this->assertInternalType('string', $model->getModelName());
        $this->assertEquals('customer_group', $model->getModelName());
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return CustomerGroupModel
     */
    protected function getModel(CustomerGroup $customerGroup)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $factory */
        $factory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $factory
            ->expects($this->exactly($customerGroup->getCustomers()->count()))
            ->method('create')
            ->will(
                $this->returnCallback(
                    function (array $arguments) use ($factory) {
                        $customer = reset($arguments);

                        return new CustomerModel($customer, $factory, $factory);
                    }
                )
            );

        return new CustomerGroupModel($customerGroup, $factory);
    }


    public function testGetId()
    {
        $customerGroupId = 1;
        $customerGroup = new CustomerGroup();

        $reflection = new \ReflectionProperty(get_class($customerGroup), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($customerGroup, $customerGroupId);

        $model = $this->getModel($customerGroup);
        $this->assertEquals($customerGroupId, $model->getId());
    }
}
