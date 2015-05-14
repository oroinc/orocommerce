<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Model;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel;
use OroB2B\Bundle\CustomerBundle\Model\CustomerModel;

class CustomerModelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetParent()
    {
        $customer = new Customer();
        $customer->setName('customer');
        $parent = new Customer();
        $parent
            ->setName('parent')
            ->addChild($customer);

        $model = $this->getModel($customer);

        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Model\CustomerModel', $model->getParent());
        $this->assertEquals([$parent], $model->getParent()->getEntities());
    }

    public function testGetParentEmpty()
    {
        $customer = new Customer();
        $customer->setName('customer');

        $model = $this->getModel($customer);

        $this->assertNull($model->getParent());
    }

    public function testGetGroup()
    {
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);

        $model = $this->getModel($customer);

        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel', $model->getGroup());
        $this->assertEquals([$customerGroup], $model->getGroup()->getEntities());
    }

    public function testGetGroupEmpty()
    {
        $customer = new Customer();
        $model = $this->getModel($customer);

        $this->assertNull($model->getGroup());
    }

    public function testGetChildren()
    {
        $customer1 = new Customer();
        $customer1->setName('customer1');
        $customer2 = new Customer();
        $customer2->setName('customer2');
        $parent = new Customer();
        $parent
            ->setName('parent')
            ->addChild($customer1)
            ->addChild($customer2);

        $model = $this->getModel($parent);

        foreach ($model->getChildren() as $key => $customerModel) {
            $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Model\CustomerModel', $customerModel);

            $this->assertEquals(
                [$parent->getChildren()->get($key)],
                $customerModel->getEntities()
            );
        }
    }

    public function testGetChildrenEmpty()
    {
        $customer = new Customer();
        $model = $this->getModel($customer);

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $model->getChildren());
        $this->assertTrue($model->getChildren()->isEmpty());
    }

    public function testGetSetName()
    {
        $name = 'customer';
        $customer = new Customer();
        $customer->setName($name);
        $model = $this->getModel($customer);

        $this->assertInternalType('string', $model->getName());
        $this->assertEquals($name, $model->getName());

        $alteredName = 'alteredCustomer';
        $model->setName($alteredName);

        $this->assertEquals($alteredName, $customer->getName());
        $this->assertEquals($alteredName, $model->getName());
    }

    public function testGetNameEmpty()
    {
        $customer = new Customer();
        $model = $this->getModel($customer);

        $this->assertNull($model->getName());
    }

    /**
     * @param Customer $customer
     *
     * @return CustomerModel
     */
    protected function getModel(Customer $customer)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $customerFactory */
        $customerFactory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $groupFactory */
        $groupFactory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $customerFactory
            ->expects($this->any())
            ->method('create')
            ->will(
                $this->returnCallback(
                    function (array $arguments) use ($groupFactory, $customerFactory) {
                        $customer = reset($arguments);

                        return new CustomerModel($customer, $groupFactory, $customerFactory);
                    }
                )
            );

        $groupFactory
            ->expects($this->any())
            ->method('create')
            ->will(
                $this->returnCallback(
                    function (array $arguments) use ($customerFactory) {
                        $group = reset($arguments);

                        return new CustomerGroupModel($group, $customerFactory);
                    }
                )
            );

        return new CustomerModel($customer, $groupFactory, $customerFactory);
    }

    public function testGetModelName()
    {
        $customer = new Customer();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $factory */
        $factory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $model = new CustomerModel($customer, $factory, $factory);

        $this->assertInternalType('string', $model->getModelName());
        $this->assertEquals('customer', $model->getModelName());
    }

    public function testGetId()
    {
        $customerId = 1;
        $customer = new Customer();

        $reflection = new \ReflectionProperty(get_class($customer), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($customer, $customerId);

        $model = $this->getModel($customer);
        $this->assertEquals($customerId, $model->getId());
    }
}
