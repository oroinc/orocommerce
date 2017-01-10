<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerUserRoleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testRole()
    {
        $name = 'test role#$%';
        $role = new CustomerUserRole();
        $account = new Customer();
        $organization = new Organization();

        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getLabel());
        $this->assertEmpty($role->getRole());
        $this->assertEmpty($role->getOrganization());
        $this->assertEmpty($role->getAccount());

        $role->setAccount($account);
        $role->setOrganization($organization);

        $this->assertEquals($organization, $role->getOrganization());
        $this->assertEquals($account, $role->getAccount());

        $role->setLabel($name);
        $this->assertEquals($name, $role->getLabel());

        $this->assertEquals(CustomerUserRole::PREFIX_ROLE, $role->getPrefix());

        $role->setRole($name);
        $this->assertStringStartsWith(CustomerUserRole::PREFIX_ROLE . 'TEST_ROLE_', $role->getRole());

        $this->assertEquals($name, (string)$role);
    }

    /**
     * Test CustomerUserRole relations
     */
    public function testRelations()
    {
        static::assertPropertyCollections(new CustomerUserRole(), [
            ['websites', new Website()],
            ['accountUsers', new CustomerUser()],
        ]);

        static::assertPropertyAccessors(new CustomerUserRole(), [
            ['account', new Customer()],
            ['organization', new Organization()]
        ]);
    }

    public function testNotEmptyRole()
    {
        $name = 'another test role';
        $role = new CustomerUserRole($name);
        $this->assertEquals(CustomerUserRole::PREFIX_ROLE . 'ANOTHER_TEST_ROLE', $role->getRole());
    }


    public function testSelfManaged()
    {
        $role = new CustomerUserRole('test');

        $this->assertFalse($role->isSelfManaged());

        $role->setSelfManaged(true);

        $this->assertTrue($role->isSelfManaged());
    }

    public function testIsPredefined()
    {
        $name = 'Predefined role';

        $role = new CustomerUserRole($name);
        $this->assertTrue($role->isPredefined());

        $role->setAccount(new Customer());
        $this->assertFalse($role->isPredefined());
    }
}
