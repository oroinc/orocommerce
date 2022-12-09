<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Factory\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactory;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CheckoutFactoryTest extends TestCase
{
    use EntityTrait;

    private CheckoutFactory $checkoutFactory;

    protected function setUp(): void
    {
        $this->checkoutFactory = new CheckoutFactory(PropertyAccess::createPropertyAccessor());
    }

    public function testCreateCheckout()
    {
        $billingAddress = new OrderAddress();
        ReflectionUtil::setId($billingAddress, 1);

        $shippingAddress = new OrderAddress();
        ReflectionUtil::setId($shippingAddress, 2);

        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 1);

        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem2, 2);

        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);

        $checkoutSource = new CheckoutSource();
        $shipUntil = new \DateTime('now');

        $checkout = $this->getEntity(Checkout::class, [
            'id' => 1,
            'paymentMethod' => 'PAYMENT',
            'currency' => 'USD',
            'source' => $checkoutSource,
            'completed' => false,
            'shippingMethod' => 'SHIPPING_METHOD',
            'shippingMethodType' => 'SHIPPING_METHOD_TYPE',
            'customerNotes' => 'Note',
            'billingAddress' => $billingAddress,
            'shippingAddress' => $shippingAddress,
            'lineItems' => new ArrayCollection([$lineItem1, $lineItem2]),
            'organization' => $organization,
            'registeredCustomerUser' => $customerUser,
            'poNumber' => '123',
            'shipUntil' => $shipUntil,
        ]);

        $lineItem3 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem3, 3);

        $lineItem4 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem4, 4);

        $resultCheckout = $this->checkoutFactory->createCheckout($checkout, [$lineItem3, $lineItem4]);

        $this->assertNull($resultCheckout->getId());
        $this->assertEquals('PAYMENT', $resultCheckout->getPaymentMethod());
        $this->assertEquals('USD', $resultCheckout->getCurrency());
        $this->assertEquals('SHIPPING_METHOD', $resultCheckout->getShippingMethod());
        $this->assertEquals('SHIPPING_METHOD_TYPE', $resultCheckout->getShippingMethodType());
        $this->assertEquals('Note', $resultCheckout->getCustomerNotes());
        $this->assertFalse($resultCheckout->isCompleted());
        $this->assertEquals('123', $resultCheckout->getPoNumber());
        $this->assertEquals($shipUntil, $resultCheckout->getShipUntil());

        $this->assertEquals(1, $resultCheckout->getBillingAddress()->getId());
        $this->assertEquals(2, $resultCheckout->getShippingAddress()->getId());
        $this->assertEquals(1, $resultCheckout->getOrganization()->getId());
        $this->assertEquals(1, $resultCheckout->getRegisteredCustomerUser()->getId());

        $resultLineItemsIds = $resultCheckout->getLineItems()
            ->map(fn (CheckoutLineItem $item) => $item->getId())
            ->toArray();

        sort($resultLineItemsIds);
        $this->assertEquals([3, 4], $resultLineItemsIds);
    }
}
