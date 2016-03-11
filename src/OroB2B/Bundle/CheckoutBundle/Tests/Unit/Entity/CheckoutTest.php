<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class CheckoutTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['billingAddress', new OrderAddress()],
            ['saveBillingAddress', true],
            ['shipToBillingAddress', true],
            ['shippingAddress', new OrderAddress()],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['poNumber', 'PO-#1'],
            ['customerNotes', 'customer notes'],
            ['shipUntil', $now],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['website', new Website()],
            ['source', new CheckoutSource()]
        ];

        $entity = new Checkout();
        $this->assertPropertyAccessors($entity, $properties);
    }
}
