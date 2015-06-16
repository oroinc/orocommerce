<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserRoleTest extends \PHPUnit_Framework_TestCase
{
    public function testRole()
    {
        $name = 'test role#$%';
        $role = new AccountUserRole($name);

        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getLabel());
        $this->assertEmpty($role->getRole());

        $role->setLabel($name);
        $this->assertEquals($name, $role->getLabel());

        $this->assertEquals(AccountUserRole::PREFIX_ROLE, $role->getPrefix());

        $role->setRole($name);
        $this->assertStringStartsWith(AccountUserRole::PREFIX_ROLE . 'TEST_ROLE_', $role->getRole());

        $this->assertEquals($name, (string)$role);
    }
}
