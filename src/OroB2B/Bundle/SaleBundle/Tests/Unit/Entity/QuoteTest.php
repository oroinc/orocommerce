<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

class QuoteTest extends AbstractTest
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['qid', 'QID-123456'],
            ['owner', new User()],
            ['accountUser', new AccountUser()],
            ['account', new Account()],
            ['organization', new Organization()],
            ['validUntil', $now, false],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['request', new Request()],
        ];

        static::assertPropertyAccessors(new Quote(), $properties);

        static::assertPropertyCollections(new Quote(), [
            ['quoteProducts', new QuoteProduct()],
        ]);
    }

    public function testPrePersist()
    {
        $quote = new Quote();

        $this->assertNull($quote->getCreatedAt());
        $this->assertNull($quote->getUpdatedAt());

        $quote->prePersist();

        $this->assertInstanceOf('\DateTime', $quote->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $quote->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $quote = new Quote();

        $this->assertNull($quote->getUpdatedAt());

        $quote->preUpdate();

        $this->assertInstanceOf('\DateTime', $quote->getUpdatedAt());
    }

    public function testAddQuoteProduct()
    {
        $quote          = new Quote();
        $quoteProduct   = new QuoteProduct();

        $this->assertNull($quoteProduct->getQuote());

        $quote->addQuoteProduct($quoteProduct);

        $this->assertEquals($quote, $quoteProduct->getQuote());
    }
}
