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
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
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
            ['parent', new Order],
        ];

        $order = new Order();
        $this->assertPropertyAccessors($order, $properties);
        $this->assertPropertyCollection($order, 'lineItems', new OrderLineItem());
        $this->assertPropertyCollection($order, 'discounts', new OrderDiscount());
        $this->assertPropertyCollection($order, 'shippingTrackings', new OrderShippingTracking());
        $this->assertPropertyCollection($order, 'subOrders', new Order());
    }

    public function testSourceDocument()
    {
        $order = $this->getEntity(
            Order::class,
            [
                'identifier' => 'ident',
            ]
        );

        $this->assertSame($order, $order->getSourceDocument());
        $this->assertEquals('ident', $order->getSourceDocumentIdentifier());
    }

    public function testToString()
    {
        $order = new Order();
        $order->setIdentifier('test');
        self::assertEquals('test', (string)$order);
    }

    public function testLineItemsSetter()
    {
        $lineItems = new ArrayCollection([new OrderLineItem()]);

        $order = $this->getEntity(Order::class, ['id' => 42]);
        $order->setLineItems($lineItems);

        $result = $order->getLineItems();

        $this->assertEquals($lineItems, $result);
        foreach ($result as $lineItem) {
            $this->assertEquals($lineItem->getOrder()->getId(), $order->getId());
        }
    }

    public function testGetEmail()
    {
        $email = 'test@test.com';
        $order = new Order();
        $this->assertEmpty($order->getEmail());
        $customerUser = new CustomerUser();
        $customerUser->setEmail($email);
        $order->setCustomerUser($customerUser);
        $this->assertEquals($email, $order->getEmail());
    }

    public function testGetEmailHolderName()
    {
        $order = new Order();
        $this->assertEmpty($order->getEmailHolderName());

        $customerUser = new CustomerUser();
        $customerUser->setFirstName('First');
        $customerUser->setLastName('Last');
        $order->setCustomerUser($customerUser);

        $this->assertEquals('First Last', $order->getEmailHolderName());
    }

    public function testCustomerUserToCustomerRelation()
    {
        $order = new Order();

        $customer = $this->createMock(Customer::class);
        $customer->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);

        $this->assertEmpty($order->getCustomer());
        $order->setCustomerUser($customerUser);
        $this->assertSame($customer, $order->getCustomer());
    }

    public function testPrePersist()
    {
        $order = new Order();
        $order->prePersist();
        $this->assertInstanceOf(\DateTime::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Order();
        $order->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $order->getUpdatedAt());
    }

    public function testPostLoad()
    {
        $item = new Order();

        $this->assertNull($item->getTotalDiscounts());

        $value = 100;
        $currency = 'EUR';
        ReflectionUtil::setPropertyValue($item, 'totalDiscountsAmount', $value);
        ReflectionUtil::setPropertyValue($item, 'currency', $currency);

        $item->postLoad();

        $this->assertEquals(Price::create($value, $currency), $item->getTotalDiscounts());
    }

    public function testGetEstimatedShippingCost()
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
    public function testGetShippingCost(?int $estimated, ?int $overridden, ?int $expected)
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

    public function testGetShippingCostNull()
    {
        self::assertNull((new Order())->getShippingCost());
    }

    public function testUpdateTotalDiscounts()
    {
        $item = new Order();
        $value = 1000;
        $currency = 'EUR';
        $item->setTotalDiscounts(Price::create($value, $currency));

        $item->updateTotalDiscounts();

        $this->assertEquals($value, ReflectionUtil::getPropertyValue($item, 'totalDiscountsAmount'));
    }

    public function testSetTotalDiscounts()
    {
        $value = 99;
        $currency = 'EUR';
        $price = Price::create($value, $currency);

        $item = new Order();
        $item->setTotalDiscounts($price);

        $this->assertEquals($price, $item->getTotalDiscounts());

        $this->assertEquals($value, ReflectionUtil::getPropertyValue($item, 'totalDiscountsAmount'));
    }

    public function testGetProductsFromLineItems()
    {
        $firstProduct = $this->getEntity(Product::class, ['id' => 1]);
        $secondProduct = $this->getEntity(Product::class, ['id' => 5]);

        $order = $this->getEntity(Order::class, [
            'lineItems' => [
                $this->getEntity(OrderLineItem::class, ['id' => 1, 'product' => $firstProduct]),
                $this->getEntity(OrderLineItem::class, ['id' => 2, 'product' => $secondProduct])
            ]
        ]);

        $this->assertEquals([$firstProduct, $secondProduct], $order->getProductsFromLineItems());
    }
}
