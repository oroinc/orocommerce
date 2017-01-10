<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Unit\Traits\AddressEntityTestTrait;

class AccountTest extends \PHPUnit_Framework_TestCase
{
    use AddressEntityTestTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createAccountEntity(), [
            ['id', 42],
            ['name', 'Adam Weishaupt'],
            ['parent', $this->createAccountEntity()],
            ['group', $this->createAccountGroupEntity()],
            ['organization', $this->createOrganization()],
        ]);
        $this->assertPropertyCollections(new Customer(), [
            ['users', new CustomerUser()],
            ['salesRepresentatives', new User()],
        ]);
    }

    /**
     * Test children
     */
    public function testChildrenCollection()
    {
        $parentAccount = $this->createAccountEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $parentAccount->getChildren());
        $this->assertCount(0, $parentAccount->getChildren());

        $account = $this->createAccountEntity();

        $this->assertInstanceOf(
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            $parentAccount->addChild($account)
        );

        $this->assertCount(1, $parentAccount->getChildren());

        $parentAccount->addChild($account);

        $this->assertCount(1, $parentAccount->getChildren());

        $parentAccount->removeChild($account);

        $this->assertCount(0, $parentAccount->getChildren());
    }

    /**
     * @return CustomerGroup
     */
    protected function createAccountGroupEntity()
    {
        return new CustomerGroup();
    }

    /**
     * @return Customer
     */
    protected function createAccountEntity()
    {
        return new Customer();
    }

    /**
     * @return CustomerUser
     */
    protected function createUserEntity()
    {
        return new CustomerUser();
    }

    /**
     * @return Organization
     */
    protected function createOrganization()
    {
        return new Organization();
    }

    /**
     * @return CustomerAddress
     */
    protected function createAddressEntity()
    {
        return new CustomerAddress();
    }

    /**
     * @return Customer
     */
    protected function createTestedEntity()
    {
        return $this->createAccountEntity();
    }
}
