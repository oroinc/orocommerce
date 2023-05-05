<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['qid', 'QID-123456'],
            ['guestAccessId', UUIDGenerator::v4(), false],
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
            ['pricesChanged', true, false],
            ['shippingMethodLocked', true],
            ['allowUnlistedShippingMethod', true],
            ['request', new Request()],
            ['website', new Website(), true],
            ['currency', 'UAH'],
            ['estimatedShippingCostAmount', 15],
            ['overriddenShippingCostAmount', 15],
        ];

        self::assertPropertyAccessors(new Quote(), $properties);

        $quote = new Quote();
        self::assertIsUUID($quote->getGuestAccessId());

        self::assertPropertyCollections(new Quote(), [
            ['quoteProducts', new QuoteProduct()],
            ['assignedUsers', new User()],
            ['assignedCustomerUsers', new CustomerUser()],
        ]);
    }

    public function testToString(): void
    {
        $quote = new Quote();
        ReflectionUtil::setId($quote, 123);

        $this->assertSame('123', (string)$quote);
    }

    public function testGetEmail(): void
    {
        $quote = new Quote();
        $this->assertEmpty($quote->getEmail());
        $customerUser = new CustomerUser();
        $customerUser->setEmail('test');
        $quote->setCustomerUser($customerUser);
        $this->assertEquals('test', $quote->getEmail());
    }

    public function testPrePersist(): void
    {
        $quote = new Quote();

        $this->assertNull($quote->getCreatedAt());
        $this->assertNull($quote->getUpdatedAt());

        $quote->prePersist();

        $this->assertInstanceOf(\DateTime::class, $quote->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $quote->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $quote = new Quote();

        $this->assertNull($quote->getUpdatedAt());

        $quote->preUpdate();

        $this->assertInstanceOf(\DateTime::class, $quote->getUpdatedAt());
    }

    public function testGetEmailOwner(): void
    {
        $customerUser = new CustomerUser();
        $quote = new Quote();
        $quote->setCustomerUser($customerUser);

        $this->assertEquals($customerUser, $quote->getEmailOwner());
    }

    public function testAddQuoteProduct(): void
    {
        $quote = new Quote();
        $quoteProduct = new QuoteProduct();

        $this->assertNull($quoteProduct->getQuote());

        $quote->addQuoteProduct($quoteProduct);

        $this->assertEquals($quote, $quoteProduct->getQuote());
    }

    /**
     * @dataProvider hasOfferVariantsDataProvider
     */
    public function testHasOfferVariants(Quote $quote, bool $expected): void
    {
        $this->assertEquals($expected, $quote->hasOfferVariants());
    }

    public function hasOfferVariantsDataProvider(): array
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
     */
    public function testIsAcceptable(
        bool $expired,
        ?\DateTime $validUntil,
        bool $expected,
        ?string $internalStatus = Quote::INTERNAL_STATUS_SENT_TO_CUSTOMER
    ): void {
        $status = $internalStatus ? new TestEnumValue($internalStatus, 'test') : null;

        $quote = new Quote();
        $quote->setExpired($expired)
            ->setValidUntil($validUntil)
            ->setInternalStatus($status);
        $this->assertEquals($expected, $quote->isAcceptable());
    }

    public function isAcceptableDataProvider(): \Generator
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
     * @dataProvider isAvailableOnFrontendProvider
     */
    public function testIsAvailableOnFrontend(string $internalStatus, bool $expected): void
    {
        $quote = new Quote();
        $quote->setInternalStatus(new TestEnumValue($internalStatus, 'test'));

        $this->assertEquals($expected, $quote->isAvailableOnFrontend());
    }

    public function isAvailableOnFrontendProvider(): array
    {
        return [
            ['template', true],
            ['open', true],
            ['sent_to_customer', true],
            ['expired', true],
            ['accepted', true],
            ['declined', true],
            ['cancelled', true],
            ['test', false],
        ];
    }

    public function testClone(): void
    {
        $quote = new Quote();
        $this->assertIsUUID($quote->getGuestAccessId());

        $clone = clone $quote;
        $this->assertIsUUID($clone->getGuestAccessId());
        $this->assertNotEquals($quote->getGuestAccessId(), $clone->getGuestAccessId());
    }

    private function createQuote(
        int $quoteProductCount,
        int $quoteProductOfferCount,
        bool $allowIncrements = false
    ): Quote {
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

    private static function assertIsUUID(string $actual): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i',
            $actual
        );
    }

    /**
     * @dataProvider shippingCostDataProvider
     */
    public function testGetShippingCost(?int $estimated, ?int $overridden, ?int $expected)
    {
        $currency = 'USD';
        $item = new Quote();
        $item->setCurrency($currency);
        $item->setEstimatedShippingCostAmount($estimated);
        $item->setOverriddenShippingCostAmount($overridden);

        if (null !== $expected) {
            self::assertEquals(Price::create($expected, $currency), $item->getShippingCost());
        } else {
            self::assertNull($item->getShippingCost());
        }
    }

    public function shippingCostDataProvider(): array
    {
        return [
            [
                'estimated' => 10,
                'overridden' => null,
                'expected' => 10
            ],
            [
                'estimated' => null,
                'overridden' => 20,
                'expected' => 20
            ],
            [
                'estimated' => 10,
                'overridden' => 30,
                'expected' => 30
            ],
            [
                'estimated' => 10,
                'overridden' => 0,
                'expected' => null
            ]
        ];
    }

    public function testGetShippingCostNull()
    {
        self::assertNull((new Quote())->getShippingCost());
    }
}
