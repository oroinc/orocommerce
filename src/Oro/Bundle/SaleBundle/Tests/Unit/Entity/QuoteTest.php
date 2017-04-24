<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteTest extends AbstractTest
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['qid', 'QID-123456'],
            ['owner', new User()],
            ['customerUser', new CustomerUser()],
            ['shippingAddress', new QuoteAddress()],
            ['customer', new Customer()],
            ['organization', new Organization()],
            ['poNumber', '1'],
            ['validUntil', $now, false],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['shipUntil', $now, false],
            ['expired', true],
            ['shippingMethodLocked', true],
            ['allowUnlistedShippingMethod', true],
            ['request', new Request()],
            ['website', new Website()],
            ['currency', 'UAH'],
            ['estimatedShippingCostAmount', 15],
            ['overriddenShippingCostAmount', 15],
        ];

        static::assertPropertyAccessors(new Quote(), $properties);

        static::assertPropertyCollections(new Quote(), [
            ['quoteProducts', new QuoteProduct()],
            ['assignedUsers', new User()],
            ['assignedCustomerUsers', new CustomerUser()],
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
        $customerUser = new CustomerUser();
        $customerUser->setEmail('test');
        $quote->setCustomerUser($customerUser);
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
     * @dataProvider isAcceptableDataProvider
     *
     * @param bool $expired
     * @param \DateTime|null $validUntil
     * @param bool $expected
     * @param string $internalStatus
     */
    public function testIsAcceptable(
        $expired,
        $validUntil,
        $expected,
        $internalStatus = Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER
    ) {
        $status = $internalStatus ? new StubEnumValue($internalStatus, 'test') : null;

        $quote = new Quote();
        $quote->setExpired($expired)
            ->setValidUntil($validUntil)
            ->setInternalStatus($status);
        $this->assertEquals($expected, $quote->isAcceptable());
    }

    /**
     * @return \Generator
     */
    public function isAcceptableDataProvider()
    {
        yield [
            'expired' => false,
            'validUntil' => null,
            'expected' => true
        ];

        yield [
            'expired' => false,
            'validUntil' => new \DateTime('+1 day'),
            'expected' => true
        ];

        yield [
            'expired' => false,
            'validUntil' => new \DateTime('-1 day'),
            'expected' => false
        ];

        yield [
            'expired' => true,
            'validUntil' => null,
            'expected' => false
        ];

        yield [
            'expired' => true,
            'validUntil' => new \DateTime('+1 day'),
            'expected' => false
        ];

        yield [
            'expired' => true,
            'validUntil' => new \DateTime('-1 day'),
            'expected' => false
        ];

        yield [
            'expired' => false,
            'validUntil' => new \DateTime('+1 day'),
            'expected' => false,
            'internalStatus' => Quote::INTERNAL_STATUS_DELETED
        ];

        yield [
            'expired' => false,
            'validUntil' => new \DateTime('+1 day'),
            'expected' => false,
            'internalStatus' => null
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
