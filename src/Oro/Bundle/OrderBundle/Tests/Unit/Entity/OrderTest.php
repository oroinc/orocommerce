<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
            ['identifier', 'identifier-test-01'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['shippingAddress', new OrderAddress()],
            ['billingAddress', new OrderAddress()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['poNumber', 'PO-#1'],
            ['customerNotes', 'customer notes'],
            ['shipUntil', $now],
            ['currency', 'USD'],
            ['subtotal', 999.99],
            ['total', 999.99],
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['website', new Website()],
            ['sourceEntityClass', 'EntityClass'],
            ['sourceEntityIdentifier', 'source-identifier-test-01'],
            ['sourceEntityId', 1],
            ['estimatedShippingCostAmount', 10.1],
            ['overriddenShippingCostAmount', 11.2],
            ['totalDiscounts', new Price()],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type'],
            ['parent', new Order()],
            ['createdBy', new User()],
            ['external', true, false],
        ];

        $order = new Order();
        self::assertPropertyAccessors($order, $properties);
        self::assertPropertyCollection($order, 'lineItems', new OrderLineItem());
        self::assertPropertyCollection($order, 'discounts', new OrderDiscount());
        self::assertPropertyCollection($order, 'shippingTrackings', new OrderShippingTracking());
        self::assertPropertyCollection($order, 'subOrders', new Order());
    }

    public function testSourceDocument(): void
    {
        $order = new Order();
        $order->setIdentifier('ident');

        self::assertSame($order, $order->getSourceDocument());
        self::assertEquals('ident', $order->getSourceDocumentIdentifier());
    }

    public function testToString(): void
    {
        $order = new Order();
        $order->setIdentifier('test');
        self::assertEquals('test', (string)$order);
    }

    public function testLineItemsSetter(): void
    {
        $order = new Order();
        $lineItems = new ArrayCollection([new OrderLineItem()]);
        $order->setLineItems($lineItems);

        $result = $order->getLineItems();

        self::assertSame($lineItems, $result);
        foreach ($result as $lineItem) {
            self::assertSame($order, $lineItem->getOrder());
        }
    }

    public function testGetEmail(): void
    {
        $email = 'test@test.com';
        $order = new Order();
        self::assertEmpty($order->getEmail());
        $customerUser = new CustomerUser();
        $customerUser->setEmail($email);
        $order->setCustomerUser($customerUser);
        self::assertEquals($email, $order->getEmail());
    }

    public function testGetEmailHolderName(): void
    {
        $order = new Order();
        self::assertEmpty($order->getEmailHolderName());

        $customerUser = new CustomerUser();
        $customerUser->setFirstName('First');
        $customerUser->setLastName('Last');
        $order->setCustomerUser($customerUser);

        self::assertEquals('First Last', $order->getEmailHolderName());
    }

    public function testCustomerUserToCustomerRelation(): void
    {
        $order = new Order();

        $customer = $this->createMock(Customer::class);
        $customer->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        self::assertEmpty($order->getCustomer());
        $order->setCustomerUser($customerUser);
        self::assertSame($customer, $order->getCustomer());
    }

    public function testPrePersist(): void
    {
        $order = new Order();
        $order->prePersist();
        self::assertInstanceOf(\DateTime::class, $order->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $order->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $order = new Order();
        $order->preUpdate();
        self::assertInstanceOf(\DateTime::class, $order->getUpdatedAt());
    }

    public function testPostLoad(): void
    {
        $item = new Order();

        self::assertNull($item->getTotalDiscounts());

        $value = 100;
        $currency = 'EUR';
        ReflectionUtil::setPropertyValue($item, 'totalDiscountsAmount', $value);
        ReflectionUtil::setPropertyValue($item, 'currency', $currency);

        $item->postLoad();

        self::assertEquals(Price::create($value, $currency), $item->getTotalDiscounts());
    }

    public function testGetEstimatedShippingCost(): void
    {
        $value = 10;
        $currency = 'USD';
        $item = new Order();
        self::assertNull($item->getEstimatedShippingCost());
        $item->setCurrency($currency);
        $item->setEstimatedShippingCostAmount($value);
        self::assertEquals(Price::create($value, $currency), $item->getEstimatedShippingCost());
    }

    /**
     * @dataProvider shippingCostDataProvider
     */
    public function testGetShippingCost(?int $estimated, ?int $overridden, ?int $expected): void
    {
        $currency = 'USD';
        $item = new Order();
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
                'expected' => 0
            ],
            [
                'estimated' => null,
                'overridden' => null,
                'expected' => null
            ]
        ];
    }

    public function testGetShippingCostNull(): void
    {
        self::assertNull((new Order())->getShippingCost());
    }

    public function testUpdateTotalDiscounts(): void
    {
        $item = new Order();
        $value = 1000;
        $currency = 'EUR';
        $item->setTotalDiscounts(Price::create($value, $currency));

        $item->updateTotalDiscounts();

        self::assertEquals($value, ReflectionUtil::getPropertyValue($item, 'totalDiscountsAmount'));
    }

    public function testSetTotalDiscounts(): void
    {
        $value = 99;
        $currency = 'EUR';
        $price = Price::create($value, $currency);

        $item = new Order();
        $item->setTotalDiscounts($price);

        self::assertEquals($price, $item->getTotalDiscounts());

        self::assertEquals($value, ReflectionUtil::getPropertyValue($item, 'totalDiscountsAmount'));
    }

    public function testGetProductsFromLineItems(): void
    {
        $firstProduct = new Product();
        ReflectionUtil::setId($firstProduct, 1);
        $secondProduct = new Product();
        ReflectionUtil::setId($secondProduct, 5);

        $orderLineItem1 = new OrderLineItem();
        $orderLineItem1->setProduct($firstProduct);
        $orderLineItem2 = new OrderLineItem();
        $orderLineItem2->setProduct($secondProduct);

        $order = new Order();
        $order->addLineItem($orderLineItem1);
        $order->addLineItem($orderLineItem2);

        self::assertEquals([$firstProduct, $secondProduct], $order->getProductsFromLineItems());
    }

    public function testPdfDocumentsCollection(): void
    {
        self::assertPropertyCollection(
            new Order(),
            'pdfDocuments',
            new PdfDocument()
        );
    }
}
