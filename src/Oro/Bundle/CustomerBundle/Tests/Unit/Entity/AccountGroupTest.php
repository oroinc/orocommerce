<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\Account;

class AccountGroupTest extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createAccountGroupEntity(), [
            ['id', 42],
            ['name', 'Illuminatenorden'],
        ]);
    }

    /**
     * Test accounts
     */
    public function testAccountCollection()
    {
        $accountGroup = $this->createAccountGroupEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $accountGroup->getAccounts());
        $this->assertCount(0, $accountGroup->getAccounts());

        $account = $this->createAccountEntity();

        $this->assertInstanceOf(
            'Oro\Bundle\CustomerBundle\Entity\AccountGroup',
            $accountGroup->addAccount($account)
        );

        $this->assertCount(1, $accountGroup->getAccounts());

        $accountGroup->addAccount($account);

        $this->assertCount(1, $accountGroup->getAccounts());

        $accountGroup->removeAccount($account);

        $this->assertCount(0, $accountGroup->getAccounts());
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
}
