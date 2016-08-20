<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderTest extends \PHPUnit_Framework_TestCase
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
            ['paymentTerm', new PaymentTerm()],
            ['account', new Account()],
            ['accountUser', new AccountUser()],
            ['website', new Website()],
            ['shippingCost', new Price()],
            ['sourceEntityClass', 'EntityClass'],
            ['sourceEntityIdentifier', 'source-identifier-test-01'],
            ['sourceEntityId', 1],
            ['shippingCost', new Price()],
            ['totalDiscounts', new Price()],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type'],
        ];

        $order = new Order();
        $this->assertPropertyAccessors($order, $properties);
        $this->assertPropertyCollection($order, 'lineItems', new OrderLineItem());
        $this->assertPropertyCollection($order, 'discounts', new OrderDiscount());
    }

    public function testLineItemsSetter()
    {
        $lineItems = new ArrayCollection([new OrderLineItem()]);

        /** @var Order $order */
        $order = $this->getEntity('Oro\Bundle\OrderBundle\Entity\Order', ['id' => 42]);
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
        $accountUser = new AccountUser();
        $accountUser->setEmail($email);
        $order->setAccountUser($accountUser);
        $this->assertEquals($email, $order->getEmail());
    }

    public function testAccountUserToAccountRelation()
    {
        $order = new Order();

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMock('Oro\Bundle\AccountBundle\Entity\Account');
        $account->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $this->assertEmpty($order->getAccount());
        $order->setAccountUser($accountUser);
        $this->assertSame($account, $order->getAccount());
    }

    public function testPrePersist()
    {
        $order = new Order();
        $order->prePersist();
        $this->assertInstanceOf('\DateTime', $order->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Order();
        $order->preUpdate();
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPostLoad()
    {
        $item = new Order();

        $this->assertNull($item->getShippingCost());
        $this->assertNull($item->getTotalDiscounts());

        $value = 100;
        $currency = 'EUR';
        $this->setProperty($item, 'shippingCostAmount', $value);
        $this->setProperty($item, 'totalDiscountsAmount', $value);
        $this->setProperty($item, 'currency', $currency);

        $item->postLoad();

        $this->assertEquals(Price::create($value, $currency), $item->getShippingCost());
        $this->assertEquals(Price::create($value, $currency), $item->getTotalDiscounts());
    }

    public function testUpdateShippingCost()
    {
        $item = new Order();
        $value = 1000;
        $currency = 'EUR';
        $item->setShippingCost(Price::create($value, $currency));

        $item->updateShippingCost();

        $this->assertEquals($value, $this->getProperty($item, 'shippingCostAmount'));
    }

    public function testSetShippingEstimate()
    {
        $value = 10;
        $currency = 'USD';
        $price = Price::create($value, $currency);

        $item = new Order();
        $item->setShippingCost($price);

        $this->assertEquals($price, $item->getShippingCost());

        $this->assertEquals($value, $this->getProperty($item, 'shippingCostAmount'));
    }

    public function testUpdateTotalDiscounts()
    {
        $item = new Order();
        $value = 1000;
        $currency = 'EUR';
        $item->setTotalDiscounts(Price::create($value, $currency));

        $item->updateTotalDiscounts();

        $this->assertEquals($value, $this->getProperty($item, 'totalDiscountsAmount'));
    }

    public function testSetTotalDiscounts()
    {
        $value = 99;
        $currency = 'EUR';
        $price = Price::create($value, $currency);

        $item = new Order();
        $item->setTotalDiscounts($price);

        $this->assertEquals($price, $item->getTotalDiscounts());

        $this->assertEquals($value, $this->getProperty($item, 'totalDiscountsAmount'));
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     *
     * @return OrderTest
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);

        return $this;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
