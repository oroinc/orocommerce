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
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

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
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['website', new Website()],
            ['source', new CheckoutSource()],
            ['shippingCost', Price::create(2, 'USD')],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type'],
            ['deleted', true],
            ['completed', true],
            ['completedData', new CompletedCheckoutData(['test' => 'value']), false],
            ['subtotals', new ArrayCollection([$this->createMock(CheckoutSubtotal::class)]), false],
            ['registeredCustomerUser', new CustomerUser()]
        ];

        $entity = new Checkout();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testSetCustomerUser()
    {
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $entity = new Checkout();
        $entity->setCustomerUser($customerUser);
        $this->assertSame($customer, $entity->getCustomer());
    }

    public function testPostLoad()
    {
        $value = 1;
        $currency = 'USD';

        $item = $this->getEntity(
            'Oro\Bundle\CheckoutBundle\Entity\Checkout',
            [
                'shippingEstimateAmount' => $value,
                'shippingEstimateCurrency' => $currency,
                'completedData' => ['test' => 'value']
            ]
        );

        $item->postLoad();

        $this->assertEquals(Price::create($value, $currency), $item->getShippingCost());
        $this->assertEquals(new CompletedCheckoutData(['test' => 'value']), $item->getCompletedData());
    }

    public function testUpdateShippingEstimate()
    {
        $item = new Checkout();
        $value = 1;
        $currency = 'USD';
        $item->setShippingCost(Price::create($value, $currency));

        $item->updateShippingEstimate();

        $this->assertEquals($value, $item->getShippingCost()->getValue());
        $this->assertEquals($currency, $item->getShippingCost()->getCurrency());
    }

    public function testGetVisitor()
    {
        $visitor = new CustomerVisitor();
        $visitor->setSessionId('session id');

        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor($visitor);

        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList($shoppingList);

        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        $this->assertEquals('session id', $checkout->getVisitor()->getSessionId());
    }

    /**
     * @dataProvider getLineItemsDataProvider
     */
    public function testGetLineItems(array $lineItems, array $expected)
    {
        $entity = new Checkout();

        if ($lineItems) {
            $entity->setLineItems(new ArrayCollection($lineItems));
        }

        $this->assertEquals(new ArrayCollection($expected), $entity->getLineItems());
    }

    public function getLineItemsDataProvider(): array
    {
        $lineItem1 = $this->createMock(CheckoutLineItem::class);
        $lineItem2 = $this->createMock(CheckoutLineItem::class);

        return [
            'empty' => [
                'lineItems' => [],
                'expected' => [],
            ],
            'filled, repeated' => [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem1],
                'expected' => [$lineItem1, $lineItem2],
            ],
        ];
    }
}
