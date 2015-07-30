<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Traits\AddressEntityTestTrait;

class CustomerTest extends \PHPUnit_Framework_TestCase
{
    use AddressEntityTestTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createCustomerEntity(), [
            ['id', 42],
            ['name', 'Adam Weishaupt'],
            ['parent', $this->createCustomerEntity()],
            ['group', $this->createCustomerGroupEntity()],
            ['organization', $this->createOrganization()],
        ]);
    }

    /**
     * Test children
     */
    public function testChildrenCollection()
    {
        $parentCustomer = $this->createCustomerEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $parentCustomer->getChildren());
        $this->assertCount(0, $parentCustomer->getChildren());

        $customer = $this->createCustomerEntity();

        $this->assertInstanceOf(
            'OroB2B\Bundle\AccountBundle\Entity\Account',
            $parentCustomer->addChild($customer)
        );

        $this->assertCount(1, $parentCustomer->getChildren());

        $parentCustomer->addChild($customer);

        $this->assertCount(1, $parentCustomer->getChildren());

        $parentCustomer->removeChild($customer);

        $this->assertCount(0, $parentCustomer->getChildren());
    }

    /**
     * Test users
     */
    public function testUsersCollection()
    {
        $customer = $this->createCustomerEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $customer->getUsers());
        $this->assertCount(0, $customer->getUsers());

        $user = $this->createUserEntity();

        $customer->addUser($user);
        $this->assertEquals([$user], $customer->getUsers()->toArray());

        // entity added only once
        $customer->addUser($user);
        $this->assertEquals([$user], $customer->getUsers()->toArray());

        $customer->removeUser($user);
        $this->assertCount(0, $customer->getUsers());

        // undefined user can't be removed
        $customer->removeUser($user);
        $this->assertCount(0, $customer->getUsers());
    }

    /**
     * @return AccountGroup
     */
    protected function createCustomerGroupEntity()
    {
        return new AccountGroup();
    }

    /**
     * @return Account
     */
    protected function createCustomerEntity()
    {
        return new Account();
    }

    /**
     * @return AccountUser
     */
    protected function createUserEntity()
    {
        return new AccountUser();
    }

    /**
     * @return Organization
     */
    protected function createOrganization()
    {
        return new Organization();
    }

    /**
     * @return AccountAddress
     */
    protected function createAddressEntity()
    {
        return new AccountAddress();
    }

    /**
     * @return Account
     */
    protected function createTestedEntity()
    {
        return $this->createCustomerEntity();
    }
}
