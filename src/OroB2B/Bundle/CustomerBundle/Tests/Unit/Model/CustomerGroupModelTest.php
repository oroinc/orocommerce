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

        $model = new CustomerGroupModel($customerGroup, $factory);

        foreach ($model->getCustomers() as $key => $customerModel) {
            $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Model\CustomerModel', $customerModel);

            $this->assertEquals(
                [$customerGroup->getCustomers()->get($key)],
                $customerModel->getEntities()
            );
        }
    }

    public function testGetModelName()
    {
        $customerGroup = new CustomerGroup();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface $factory */
        $factory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $model = new CustomerGroupModel($customerGroup, $factory);

        $this->assertInternalType('string', $model->getModelName());
        $this->assertEquals('customer_group', $model->getModelName());
    }
}
