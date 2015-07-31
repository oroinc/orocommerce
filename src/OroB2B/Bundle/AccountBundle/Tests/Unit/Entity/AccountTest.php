<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Traits\AddressEntityTestTrait;

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
            'OroB2B\Bundle\AccountBundle\Entity\Account',
            $parentAccount->addChild($account)
        );

        $this->assertCount(1, $parentAccount->getChildren());

        $parentAccount->addChild($account);

        $this->assertCount(1, $parentAccount->getChildren());

        $parentAccount->removeChild($account);

        $this->assertCount(0, $parentAccount->getChildren());
    }

    /**
     * Test users
     */
    public function testUsersCollection()
    {
        $account = $this->createAccountEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $account->getUsers());
        $this->assertCount(0, $account->getUsers());

        $user = $this->createUserEntity();

        $account->addUser($user);
        $this->assertEquals([$user], $account->getUsers()->toArray());

        // entity added only once
        $account->addUser($user);
        $this->assertEquals([$user], $account->getUsers()->toArray());

        $account->removeUser($user);
        $this->assertCount(0, $account->getUsers());

        // undefined user can't be removed
        $account->removeUser($user);
        $this->assertCount(0, $account->getUsers());
    }

    /**
     * @return AccountGroup
     */
    protected function createAccountGroupEntity()
    {
        return new AccountGroup();
    }

    /**
     * @return Account
     */
    protected function createAccountEntity()
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
        return $this->createAccountEntity();
    }
}
