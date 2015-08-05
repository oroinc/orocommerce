<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountUserRoleTest extends \PHPUnit_Framework_TestCase
{
    public function testRole()
    {
        $name = 'test role#$%';
        $role = new AccountUserRole();

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

    /**
     * Test relations between AccountUserRole and Websites
     */
    public function testWebsiteRelations()
    {
        $accountUserRole = new AccountUserRole();
        $website = new Website();

        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $accountUserRole->getWebsites()
        );
        $this->assertCount(0, $accountUserRole->getWebsites());

        $this->assertInstanceOf(
            'OroB2B\Bundle\AccountBundle\Entity\AccountUserRole',
            $accountUserRole->addWebsite($website)
        );
        $this->assertCount(1, $accountUserRole->getWebsites());

        $accountUserRole->addWebsite($website);
        $this->assertCount(1, $accountUserRole->getWebsites());

        $accountUserRole->removeWebsite($website);
        $this->assertCount(0, $accountUserRole->getWebsites());
    }

    public function testNotEmptyRole()
    {
        $name = 'another test role';
        $role = new AccountUserRole($name);
        $this->assertEquals(AccountUserRole::PREFIX_ROLE . 'ANOTHER_TEST_ROLE', $role->getRole());
    }
}
