<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class InvoiceEntityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['invoiceNumber', 'invoice-test'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['invoiceDate', $now, false],
            ['paymentDueDate', $now, false],
            ['currency', 'USD'],
            ['poNumber', 'po-test'],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['website', new Website()],
            ['subtotal', 12.55]
        ];

        $invoice = new Invoice();
        $this->assertPropertyAccessors($invoice, $properties);
        $this->assertPropertyCollection($invoice, 'lineItems', new InvoiceLineItem());
    }
}
