<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactory;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CheckoutFactoryTest extends \PHPUnit\Framework\TestCase
{
    private CheckoutFactory $checkoutFactory;

    protected function setUp(): void
    {
        $this->checkoutFactory = new CheckoutFactory(
            [
                'owner',
                'billingAddress',
                'currency',
                'customerNotes',
                'customer',
                'customerUser',
                'deleted',
                'completed',
                'registeredCustomerUser',
                'shippingAddress',
                'source',
                'website',
                'shippingMethod',
                'shippingMethodType',
                'paymentMethod',
                'poNumber',
                'shipUntil',
                'organization'
            ],
            PropertyAccess::createPropertyAccessor()
        );
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

        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);
        $checkout->setPaymentMethod('PAYMENT');
        $checkout->setCurrency('USD');
        $checkout->setSource($checkoutSource);
        $checkout->setCompleted(false);
        $checkout->setShippingMethod('SHIPPING_METHOD');
        $checkout->setShippingMethodType('SHIPPING_METHOD_TYPE');
        $checkout->setCustomerNotes('Note');
        $checkout->setBillingAddress($billingAddress);
        $checkout->setShippingAddress($shippingAddress);
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->setOrganization($organization);
        $checkout->setRegisteredCustomerUser($customerUser);
        $checkout->setPoNumber('123');
        $checkout->setShipUntil($shipUntil);

        $lineItem3 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem3, 3);

        $lineItem4 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem4, 4);

        $resultCheckout = $this->checkoutFactory->createCheckout($checkout, [$lineItem3, $lineItem4]);

        self::assertNull($resultCheckout->getId());
        self::assertEquals('PAYMENT', $resultCheckout->getPaymentMethod());
        self::assertEquals('USD', $resultCheckout->getCurrency());
        self::assertEquals('SHIPPING_METHOD', $resultCheckout->getShippingMethod());
        self::assertEquals('SHIPPING_METHOD_TYPE', $resultCheckout->getShippingMethodType());
        self::assertEquals('Note', $resultCheckout->getCustomerNotes());
        self::assertFalse($resultCheckout->isCompleted());
        self::assertEquals('123', $resultCheckout->getPoNumber());
        self::assertSame($shipUntil, $resultCheckout->getShipUntil());

        self::assertEquals(1, $resultCheckout->getBillingAddress()->getId());
        self::assertEquals(2, $resultCheckout->getShippingAddress()->getId());
        self::assertEquals(1, $resultCheckout->getOrganization()->getId());
        self::assertEquals(1, $resultCheckout->getRegisteredCustomerUser()->getId());

        $resultLineItemsIds = $resultCheckout->getLineItems()
            ->map(fn (CheckoutLineItem $item) => $item->getId())
            ->toArray();
        self::assertEquals([3, 4], $resultLineItemsIds);
    }
}
