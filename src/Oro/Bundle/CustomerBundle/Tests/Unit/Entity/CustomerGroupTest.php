<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Account;

class CustomerGroupTest extends EntityTestCase
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

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $accountGroup->getCustomers());
        $this->assertCount(0, $accountGroup->getCustomers());

        $account = $this->createAccountEntity();

        $this->assertInstanceOf(
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            $accountGroup->addCustomer($account)
        );

        $this->assertCount(1, $accountGroup->getCustomers());

        $accountGroup->addCustomer($account);

        $this->assertCount(1, $accountGroup->getCustomers());

        $accountGroup->removeCustomer($account);

        $this->assertCount(0, $accountGroup->getCustomers());
    }

    /**
     * @return CustomerGroup
     */
    protected function createAccountGroupEntity()
    {
        return new CustomerGroup();
    }

    /**
     * @return Account
     */
    protected function createAccountEntity()
    {
        return new Account();
    }
}
