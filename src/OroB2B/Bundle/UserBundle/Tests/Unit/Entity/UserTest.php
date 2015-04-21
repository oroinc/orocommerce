<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Entity;

use OroB2B\Bundle\UserBundle\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');

        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('John Doe', $user->getUsername());
    }
}
