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

    public function testGetGroup()
    {
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);

        $model = $this->getModel($customer);

        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel', $model->getGroup());
        $this->assertEquals([$customerGroup], $model->getGroup()->getEntities());
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
}
