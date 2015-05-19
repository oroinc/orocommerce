<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\UserAdminBundle\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $customer = new Customer();

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('test@example.com');
        $user->setCustomer($customer);

        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('test@example.com', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals($customer, $user->getCustomer());
    }

    public function testCreateCustomer()
    {
        $user = new User();
        $user->setFirstName('John')
            ->setLastName('Doe');
        $this->assertEmpty($user->getCustomer());

        // createCustomer is triggered on prePersist event
        $user->createCustomer();
        $customer = $user->getCustomer();
        $this->assertInstanceOf('OroB2B\Bundle\CustomerBundle\Entity\Customer', $customer);
        $this->assertEquals('John Doe', $customer->getName());

        // new customer created only if it not defined
        $user->setFirstName('Jane');
        $user->createCustomer();
        $this->assertEquals('John Doe', $user->getCustomer()->getName());
    }
}
