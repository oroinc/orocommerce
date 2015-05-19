<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\UserBundle\Entity\User;

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
}
