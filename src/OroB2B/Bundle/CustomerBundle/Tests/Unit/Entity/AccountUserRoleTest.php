<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class AccountUserRoleTest extends \PHPUnit_Framework_TestCase
{
    public function testRole()
    {
        $name = 'test';
        $role = new AccountUserRole($name);

        $this->assertEmpty($role->getId());
        $this->assertEquals(AccountUserRole::PREFIX_ROLE, $role->getPrefix());
        $this->assertEquals(AccountUserRole::PREFIX_ROLE . 'TEST', $role->getRole());
        $this->assertEquals($name, $role->getLabel());
        $this->assertEquals($name, (string) $role);
    }
}
