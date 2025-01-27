<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
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
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['website', new Website()],
            ['source', new CheckoutSource()],
            ['shippingCost', Price::create(2, 'USD')],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type'],
            ['deleted', true],
            ['completed', true],
            ['paymentInProgress', true],
            ['order', new Order()],
            ['additionalData', json_encode(['test' => 'value']), false],
            ['completedData', new CompletedCheckoutData(['test' => 'value']), false],
            ['subtotals', new ArrayCollection([$this->createMock(CheckoutSubtotal::class)]), false],
            ['registeredCustomerUser', new CustomerUser()]
        ];

        $checkout = new Checkout();
        self::assertPropertyAccessors($checkout, $properties);
    }

    public function testLineItemGroupShippingData(): void
    {
        $checkout = new Checkout();
        self::assertSame([], $checkout->getLineItemGroupShippingData());
        self::assertNull(ReflectionUtil::getPropertyValue($checkout, 'lineItemGroupShippingData'));

        $shippingData = ['product.category:1' => ['method' => 'method1', 'type' => 'type1']];
        $checkout->setLineItemGroupShippingData($shippingData);
        self::assertSame($shippingData, $checkout->getLineItemGroupShippingData());

        $checkout->setLineItemGroupShippingData([]);
        self::assertSame([], $checkout->getLineItemGroupShippingData());
        self::assertNull(ReflectionUtil::getPropertyValue($checkout, 'lineItemGroupShippingData'));
    }

    public function testSetCustomerUser(): void
    {
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $checkout = new Checkout();
        $checkout->setCustomerUser($customerUser);

        self::assertSame($customer, $checkout->getCustomer());
    }

    public function testPostLoad(): void
    {
        $checkout = new Checkout();
        ReflectionUtil::setPropertyValue($checkout, 'shippingEstimateAmount', 1);
        ReflectionUtil::setPropertyValue($checkout, 'shippingEstimateCurrency', 'USD');
        ReflectionUtil::setPropertyValue($checkout, 'completedData', ['test' => 'value']);

        $checkout->postLoad();

        self::assertEquals(Price::create(1, 'USD'), $checkout->getShippingCost());
        self::assertEquals(new CompletedCheckoutData(['test' => 'value']), $checkout->getCompletedData());
    }

    public function testPreSave(): void
    {
        $checkout = new Checkout();
        $checkout->setShippingCost(Price::create(1, 'USD'));
        $checkout->getCompletedData()->offsetSet('currency', 'USD');

        $checkout->preSave();

        self::assertEquals(1, ReflectionUtil::getPropertyValue($checkout, 'shippingEstimateAmount'));
        self::assertEquals('USD', ReflectionUtil::getPropertyValue($checkout, 'shippingEstimateCurrency'));
        self::assertEquals(['currency' => 'USD'], ReflectionUtil::getPropertyValue($checkout, 'completedData'));

        self::assertEquals(1, $checkout->getShippingCost()->getValue());
        self::assertEquals('USD', $checkout->getShippingCost()->getCurrency());
        self::assertEquals('USD', $checkout->getCompletedData()->getCurrency());
    }

    public function testGetVisitor(): void
    {
        $visitor = new CustomerVisitor();
        $visitor->setSessionId('session id');

        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor($visitor);

        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList($shoppingList);

        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        self::assertEquals('session id', $checkout->getVisitor()->getSessionId());
    }

    public function testGetLineItems(): void
    {
        $checkout = new Checkout();
        self::assertEquals(new ArrayCollection([]), $checkout->getLineItems());

        $lineItem1 = $this->createMock(CheckoutLineItem::class);
        $lineItem2 = $this->createMock(CheckoutLineItem::class);

        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem1]));

        self::assertEquals(new ArrayCollection([$lineItem1, $lineItem2]), $checkout->getLineItems());
    }
}
