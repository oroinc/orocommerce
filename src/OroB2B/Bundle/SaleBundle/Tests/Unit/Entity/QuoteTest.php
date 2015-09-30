<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

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

    public function testToString()
    {
        $id = '123';
        $quote = new Quote();
        $class = new \ReflectionClass($quote);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($quote, $id);

        $this->assertEquals($id, (string)$quote);
    }

    public function testGetEmail()
    {
        $quote = new Quote();
        $this->assertEmpty($quote->getEmail());
        $accountUser = new AccountUser();
        $accountUser->setEmail('test');
        $quote->setAccountUser($accountUser);
        $this->assertEquals('test', $quote->getEmail());
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

    /**
     * @dataProvider hasOfferVariantsDataProvider
     *
     * @param Quote $quote
     * @param bool $expected
     */
    public function testHasOfferVariants(Quote $quote, $expected)
    {
        $this->assertEquals($expected, $quote->hasOfferVariants());
    }

    /**
     * @return array
     */
    public function hasOfferVariantsDataProvider()
    {
        return [
            [$this->createQuote(0, 0), false],
            [$this->createQuote(1, 0), false],
            [$this->createQuote(1, 1), false],
            [$this->createQuote(2, 0), false],
            [$this->createQuote(2, 1), false],
            [$this->createQuote(1, 2), true],
            [$this->createQuote(1, 1, true), true],
        ];
    }

    /**
     * @param int $quoteProductCount
     * @param int $quoteProductOfferCount
     * @param bool|false $allowIncrements
     * @return Quote
     */
    protected function createQuote($quoteProductCount, $quoteProductOfferCount, $allowIncrements = false)
    {
        $quote = new Quote();

        for ($i = 0; $i < $quoteProductCount; $i++) {
            $quoteProduct = new QuoteProduct();

            for ($j = 0; $j < $quoteProductOfferCount; $j++) {
                $quoteProductOffer = new QuoteProductOffer();
                $quoteProductOffer->setAllowIncrements($allowIncrements);

                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
            }

            $quote->addQuoteProduct($quoteProduct);
        }

        return $quote;
    }
}
