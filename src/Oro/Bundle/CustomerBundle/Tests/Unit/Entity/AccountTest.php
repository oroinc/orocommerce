<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
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
        $this->assertPropertyCollections(new Account(), [
            ['users', new AccountUser()],
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
            'Oro\Bundle\CustomerBundle\Entity\Account',
            $parentAccount->addChild($account)
        );

        $this->assertCount(1, $parentAccount->getChildren());

        $parentAccount->addChild($account);

        $this->assertCount(1, $parentAccount->getChildren());

        $parentAccount->removeChild($account);

        $this->assertCount(0, $parentAccount->getChildren());
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
