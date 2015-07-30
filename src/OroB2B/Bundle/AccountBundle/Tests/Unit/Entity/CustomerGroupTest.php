<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class CustomerGroupTest extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createCustomerGroupEntity(), [
            ['id', 42],
            ['name', 'Illuminatenorden'],
        ]);
    }

    /**
     * Test customers
     */
    public function testCustomerCollection()
    {
        $customerGroup = $this->createCustomerGroupEntity();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $customerGroup->getAccounts());
        $this->assertCount(0, $customerGroup->getAccounts());

        $customer = $this->createCustomerEntity();

        $this->assertInstanceOf(
            'OroB2B\Bundle\AccountBundle\Entity\AccountGroup',
            $customerGroup->addAccount($customer)
        );

        $this->assertCount(1, $customerGroup->getAccounts());

        $customerGroup->addAccount($customer);

        $this->assertCount(1, $customerGroup->getAccounts());

        $customerGroup->removeAccount($customer);

        $this->assertCount(0, $customerGroup->getAccounts());
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
}
