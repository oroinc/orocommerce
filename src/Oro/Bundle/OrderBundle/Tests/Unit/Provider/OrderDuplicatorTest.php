<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Event\OrderDuplicateAfterEvent;
use Oro\Bundle\OrderBundle\Provider\OrderDuplicator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\Tests\Unit\DuplicatorTestCase;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class OrderDuplicatorTest extends DuplicatorTestCase
{
    private DuplicatorFactory $duplicatorFactory;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private OrderDuplicator $duplicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->duplicatorFactory = new DuplicatorFactory($this->createMatcherFactory(), $this->createFilterFactory());
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->duplicator = new OrderDuplicator($this->duplicatorFactory, $this->eventDispatcher);
    }

    public function testDuplicateCreatesNewOrderInstance(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNotSame($order, $duplicatedOrder);
    }

    public function testDuplicateSetsNullForIdField(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 200);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getId());
    }

    public function testDuplicateSetsNullForCreatedAtField(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 300);
        $order->setCreatedAt(new \DateTime('2026-01-01'));
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getCreatedAt());
    }

    public function testDuplicateSetsNullForUpdatedAtField(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 400);
        $order->setUpdatedAt(new \DateTime('2026-02-01'));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getUpdatedAt());
    }

    public function testDuplicateSetsNullForIdentifierField(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 500);
        $order->setIdentifier('ORDER-12345');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getIdentifier());
    }

    public function testDuplicateSetsNullForParentField(): void
    {
        $parentOrder = new Order();
        ReflectionUtil::setId($parentOrder, 600);

        $order = new Order();
        ReflectionUtil::setId($order, 601);
        $order->setParent($parentOrder);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getParent());
    }

    public function testDuplicateKeepsCustomerUser(): void
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 100);

        $order = new Order();
        ReflectionUtil::setId($order, 700);
        $order->setCustomerUser($customerUser);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($customerUser, $duplicatedOrder->getCustomerUser());
    }

    public function testDuplicateKeepsCustomer(): void
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, 200);

        $order = new Order();
        ReflectionUtil::setId($order, 800);
        $order->setCustomer($customer);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($customer, $duplicatedOrder->getCustomer());
    }

    public function testDuplicateKeepsOwner(): void
    {
        $owner = new User();
        ReflectionUtil::setId($owner, 300);

        $order = new Order();
        ReflectionUtil::setId($order, 900);
        $order->setOwner($owner);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($owner, $duplicatedOrder->getOwner());
    }

    public function testDuplicateKeepsOrganization(): void
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 400);

        $order = new Order();
        ReflectionUtil::setId($order, 1000);
        $order->setOrganization($organization);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($organization, $duplicatedOrder->getOrganization());
    }

    public function testDuplicateKeepsPoNumber(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1100);
        $order->setPoNumber('PO-2026-001');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals('PO-2026-001', $duplicatedOrder->getPoNumber());
    }

    public function testDuplicateKeepsCurrency(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1200);
        $order->setCurrency('EUR');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals('EUR', $duplicatedOrder->getCurrency());
    }

    public function testDuplicateKeepsWebsite(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 500);

        $order = new Order();
        ReflectionUtil::setId($order, 1300);
        $order->setWebsite($website);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($website, $duplicatedOrder->getWebsite());
    }

    public function testDuplicateKeepsShippingMethod(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1400);
        $order->setShippingMethod('flat_rate');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals('flat_rate', $duplicatedOrder->getShippingMethod());
    }

    public function testDuplicateKeepsShippingMethodType(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1500);
        $order->setShippingMethodType('express');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals('express', $duplicatedOrder->getShippingMethodType());
    }

    public function testDuplicateKeepsSubtotalValue(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1600);
        $order->setSubtotal(250.50);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals(250.50, $duplicatedOrder->getSubtotal());
    }

    public function testDuplicateKeepsTotalValue(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1700);
        $order->setTotal(300.75);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals(300.75, $duplicatedOrder->getTotal());
    }

    public function testDuplicateEmptiesLineItemsCollection(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 100);

        $order = new Order();
        ReflectionUtil::setId($order, 1800);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(1, $duplicatedOrder->getLineItems());
    }

    public function testDuplicateEmptiesShippingTrackingsCollection(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1900);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(0, $duplicatedOrder->getShippingTrackings());
    }

    public function testDuplicateDuplicatesBillingAddress(): void
    {
        $billingAddress = new OrderAddress();
        ReflectionUtil::setId($billingAddress, 100);
        $billingAddress->setFirstName('John');
        $billingAddress->setLastName('Doe');

        $order = new Order();
        ReflectionUtil::setId($order, 2000);
        $order->setBillingAddress($billingAddress);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNotNull($duplicatedOrder->getBillingAddress());
        self::assertNotSame($billingAddress, $duplicatedOrder->getBillingAddress());
        self::assertNull($duplicatedOrder->getBillingAddress()->getId());
        self::assertEquals('John', $duplicatedOrder->getBillingAddress()->getFirstName());
        self::assertEquals('Doe', $duplicatedOrder->getBillingAddress()->getLastName());
    }

    public function testDuplicateDuplicatesShippingAddress(): void
    {
        $shippingAddress = new OrderAddress();
        ReflectionUtil::setId($shippingAddress, 200);
        $shippingAddress->setFirstName('Jane');
        $shippingAddress->setLastName('Smith');

        $order = new Order();
        ReflectionUtil::setId($order, 2100);
        $order->setShippingAddress($shippingAddress);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNotNull($duplicatedOrder->getShippingAddress());
        self::assertNotSame($shippingAddress, $duplicatedOrder->getShippingAddress());
        self::assertNull($duplicatedOrder->getShippingAddress()->getId());
        self::assertEquals('Jane', $duplicatedOrder->getShippingAddress()->getFirstName());
        self::assertEquals('Smith', $duplicatedOrder->getShippingAddress()->getLastName());
    }

    public function testDuplicateKeepsAddressCountry(): void
    {
        $country = new Country('US');

        $billingAddress = new OrderAddress();
        $billingAddress->setCountry($country);

        $order = new Order();
        ReflectionUtil::setId($order, 2200);
        $order->setBillingAddress($billingAddress);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($country, $duplicatedOrder->getBillingAddress()->getCountry());
    }

    public function testDuplicateKeepsAddressRegion(): void
    {
        $region = new Region('US-CA');

        $billingAddress = new OrderAddress();
        $billingAddress->setRegion($region);

        $order = new Order();
        ReflectionUtil::setId($order, 2300);
        $order->setBillingAddress($billingAddress);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($region, $duplicatedOrder->getBillingAddress()->getRegion());
    }

    public function testDuplicateKeepsCustomerAddress(): void
    {
        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 300);

        $billingAddress = new OrderAddress();
        $billingAddress->setCustomerAddress($customerAddress);

        $order = new Order();
        ReflectionUtil::setId($order, 2400);
        $order->setBillingAddress($billingAddress);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($customerAddress, $duplicatedOrder->getBillingAddress()->getCustomerAddress());
    }

    public function testDuplicateKeepsCustomerUserAddress(): void
    {
        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 400);

        $billingAddress = new OrderAddress();
        $billingAddress->setCustomerUserAddress($customerUserAddress);

        $order = new Order();
        ReflectionUtil::setId($order, 2500);
        $order->setBillingAddress($billingAddress);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($customerUserAddress, $duplicatedOrder->getBillingAddress()->getCustomerUserAddress());
    }

    public function testDuplicateDuplicatesLineItems(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 100);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 200);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(5.0);

        $order = new Order();
        ReflectionUtil::setId($order, 2600);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(1, $duplicatedOrder->getLineItems());
        $duplicatedLineItem = $duplicatedOrder->getLineItems()->first();
        self::assertNotSame($lineItem, $duplicatedLineItem);
        self::assertSame($product, $duplicatedLineItem->getProduct());
        self::assertEquals(5.0, $duplicatedLineItem->getQuantity());
    }

    public function testDuplicateLineItemSetsNullForId(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 300);

        $order = new Order();
        ReflectionUtil::setId($order, 2700);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getLineItems()->first()->getId());
    }

    public function testDuplicateLineItemSetsNullForCreatedAt(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 400);
        $lineItem->setCreatedAt(new \DateTime('2026-01-15'));

        $order = new Order();
        ReflectionUtil::setId($order, 2800);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getLineItems()->first()->getCreatedAt());
    }

    public function testDuplicateLineItemSetsNullForUpdatedAt(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 500);
        $lineItem->setUpdatedAt(new \DateTime('2026-02-15'));

        $order = new Order();
        ReflectionUtil::setId($order, 2900);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNull($duplicatedOrder->getLineItems()->first()->getUpdatedAt());
    }

    public function testDuplicateLineItemEmptiesDraftsCollection(): void
    {
        $draft = new OrderLineItem();
        ReflectionUtil::setId($draft, 600);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 700);
        $lineItem->addDraft($draft);

        $order = new Order();
        ReflectionUtil::setId($order, 3000);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(0, $duplicatedOrder->getLineItems()->first()->getDrafts());
    }

    public function testDuplicateLineItemEmptiesOrdersCollection(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 800);

        $order = new Order();
        ReflectionUtil::setId($order, 3100);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(1, $order->getLineItems()->first()->getOrders());
        self::assertCount(1, $duplicatedOrder->getLineItems()->first()->getOrders());
    }

    public function testDuplicateLineItemKeepsProduct(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 200);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 900);
        $lineItem->setProduct($product);

        $order = new Order();
        ReflectionUtil::setId($order, 3200);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($product, $duplicatedOrder->getLineItems()->first()->getProduct());
    }

    public function testDuplicateLineItemKeepsProductUnit(): void
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1000);
        $lineItem->setProductUnit($productUnit);

        $order = new Order();
        ReflectionUtil::setId($order, 3300);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertSame($productUnit, $duplicatedOrder->getLineItems()->first()->getProductUnit());
    }

    public function testDuplicateLineItemShallowCopiesPrice(): void
    {
        $price = Price::create(99.99, 'USD');

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1100);
        $lineItem->setPrice($price);

        $order = new Order();
        ReflectionUtil::setId($order, 3400);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        $duplicatedPrice = $duplicatedOrder->getLineItems()->first()->getPrice();
        self::assertNotSame($price, $duplicatedPrice);
        self::assertEquals(99.99, $duplicatedPrice->getValue());
        self::assertEquals('USD', $duplicatedPrice->getCurrency());
    }

    public function testDuplicateLineItemDuplicatesKitItemLineItems(): void
    {
        $kitItem = new ProductKitItemStub(100);

        $product = new Product();
        ReflectionUtil::setId($product, 200);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $kitItemLineItem->setKitItem($kitItem);
        $kitItemLineItem->setProduct($product);
        $kitItemLineItem->setQuantity(2.0);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1200);
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $order = new Order();
        ReflectionUtil::setId($order, 3500);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        $duplicatedLineItem = $duplicatedOrder->getLineItems()->first();
        self::assertCount(1, $duplicatedLineItem->getKitItemLineItems());

        $duplicatedKitItemLineItem = $duplicatedLineItem->getKitItemLineItems()->first();
        self::assertNotSame($kitItemLineItem, $duplicatedKitItemLineItem);
        self::assertSame($kitItem, $duplicatedKitItemLineItem->getKitItem());
        self::assertSame($product, $duplicatedKitItemLineItem->getProduct());
        self::assertEquals(2.0, $duplicatedKitItemLineItem->getQuantity());
    }

    public function testDuplicateLineItemKeepsKitItemProduct(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 300);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $kitItemLineItem->setProduct($product);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1300);
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $order = new Order();
        ReflectionUtil::setId($order, 3600);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        $duplicatedKitItemLineItem = $duplicatedOrder->getLineItems()->first()->getKitItemLineItems()->first();
        self::assertSame($product, $duplicatedKitItemLineItem->getProduct());
    }

    public function testDuplicateLineItemKeepsKitItem(): void
    {
        $kitItem = new ProductKitItemStub(200);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $kitItemLineItem->setKitItem($kitItem);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1400);
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $order = new Order();
        ReflectionUtil::setId($order, 3700);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        $duplicatedKitItemLineItem = $duplicatedOrder->getLineItems()->first()->getKitItemLineItems()->first();
        self::assertSame($kitItem, $duplicatedKitItemLineItem->getKitItem());
    }

    public function testDuplicateLineItemKeepsKitItemProductUnit(): void
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode('each');

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $kitItemLineItem->setProductUnit($productUnit);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 1500);
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $order = new Order();
        ReflectionUtil::setId($order, 3800);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        $duplicatedKitItemLineItem = $duplicatedOrder->getLineItems()->first()->getKitItemLineItems()->first();
        self::assertSame($productUnit, $duplicatedKitItemLineItem->getProductUnit());
    }

    public function testDuplicateClearsExtendedEntityStorage(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 3900);
        $order->getStorage()['customField'] = 'customValue';

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        self::assertCount(1, $order->getStorage()->getArrayCopy());

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(0, $duplicatedOrder->getStorage());
    }

    public function testDuplicateDispatchesOrderDuplicateAfterEvent(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 4000);

        $eventDispatched = false;

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function ($event) use ($order, &$eventDispatched) {
                if ($event instanceof OrderDuplicateAfterEvent) {
                    $eventDispatched = true;
                    self::assertSame($order, $event->getOrder());
                    self::assertNotSame($order, $event->getDuplicatedOrder());
                }

                return $event;
            });

        $this->duplicator->duplicate($order);

        self::assertTrue($eventDispatched);
    }

    public function testDuplicateWithMultipleLineItems(): void
    {
        $product1 = new Product();
        ReflectionUtil::setId($product1, 100);

        $product2 = new Product();
        ReflectionUtil::setId($product2, 200);

        $lineItem1 = new OrderLineItem();
        ReflectionUtil::setId($lineItem1, 300);
        $lineItem1->setProduct($product1);
        $lineItem1->setQuantity(3.0);

        $lineItem2 = new OrderLineItem();
        ReflectionUtil::setId($lineItem2, 400);
        $lineItem2->setProduct($product2);
        $lineItem2->setQuantity(5.0);

        $order = new Order();
        ReflectionUtil::setId($order, 4200);
        $order->addLineItem($lineItem1);
        $order->addLineItem($lineItem2);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(2, $duplicatedOrder->getLineItems());

        $duplicatedLineItems = $duplicatedOrder->getLineItems()->toArray();
        self::assertNotSame($lineItem1, $duplicatedLineItems[0]);
        self::assertNotSame($lineItem2, $duplicatedLineItems[1]);

        self::assertSame($product1, $duplicatedLineItems[0]->getProduct());
        self::assertSame($product2, $duplicatedLineItems[1]->getProduct());

        self::assertEquals(3.0, $duplicatedLineItems[0]->getQuantity());
        self::assertEquals(5.0, $duplicatedLineItems[1]->getQuantity());
    }

    public function testDuplicateWithComplexOrder(): void
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, 100);

        $organization = new Organization();
        ReflectionUtil::setId($organization, 200);

        $billingAddress = new OrderAddress();
        $billingAddress->setFirstName('John');
        $billingAddress->setLastName('Doe');

        $shippingAddress = new OrderAddress();
        $shippingAddress->setFirstName('Jane');
        $shippingAddress->setLastName('Smith');

        $product = new Product();
        ReflectionUtil::setId($product, 300);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 400);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(10.0);

        $discount = new OrderDiscount();
        $discount->setDescription('Test Discount');
        $discount->setPercent(10.0);

        $order = new Order();
        ReflectionUtil::setId($order, 4300);
        $order->setCustomer($customer);
        $order->setOrganization($organization);
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
        $order->addLineItem($lineItem);
        $order->addDiscount($discount);
        $order->setCurrency('EUR');
        $order->setPoNumber('PO-123');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNotSame($order, $duplicatedOrder);
        self::assertNull($duplicatedOrder->getId());

        self::assertSame($customer, $duplicatedOrder->getCustomer());
        self::assertSame($organization, $duplicatedOrder->getOrganization());

        self::assertNotNull($duplicatedOrder->getBillingAddress());
        self::assertNotSame($billingAddress, $duplicatedOrder->getBillingAddress());
        self::assertEquals('John', $duplicatedOrder->getBillingAddress()->getFirstName());

        self::assertNotNull($duplicatedOrder->getShippingAddress());
        self::assertNotSame($shippingAddress, $duplicatedOrder->getShippingAddress());
        self::assertEquals('Jane', $duplicatedOrder->getShippingAddress()->getFirstName());

        self::assertCount(1, $duplicatedOrder->getLineItems());
        self::assertSame($product, $duplicatedOrder->getLineItems()->first()->getProduct());

        self::assertCount(1, $duplicatedOrder->getDiscounts());
        self::assertEquals('Test Discount', $duplicatedOrder->getDiscounts()->first()->getDescription());

        self::assertEquals('EUR', $duplicatedOrder->getCurrency());
        self::assertEquals('PO-123', $duplicatedOrder->getPoNumber());
    }

    public function testDuplicateDoesNotCopyDiscounts(): void
    {
        $discount1 = new OrderDiscount();
        $discount1->setDescription('Discount 1');
        $discount1->setPercent(5.0);

        $discount2 = new OrderDiscount();
        $discount2->setDescription('Discount 2');
        $discount2->setAmount(10.0);

        $order = new Order();
        ReflectionUtil::setId($order, 4400);
        $order->addDiscount($discount1);
        $order->addDiscount($discount2);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(2, $duplicatedOrder->getDiscounts());

        $duplicatedDiscounts = $duplicatedOrder->getDiscounts()->toArray();
        self::assertNotSame($discount1, $duplicatedDiscounts[0]);
        self::assertNotSame($discount2, $duplicatedDiscounts[1]);

        self::assertEquals('Discount 1', $duplicatedDiscounts[0]->getDescription());
        self::assertEquals('Discount 2', $duplicatedDiscounts[1]->getDescription());
    }

    public function testDuplicateWithEmptyOrder(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 4500);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertNotSame($order, $duplicatedOrder);
        self::assertNull($duplicatedOrder->getId());
        self::assertCount(0, $duplicatedOrder->getLineItems());
        self::assertNull($duplicatedOrder->getBillingAddress());
        self::assertNull($duplicatedOrder->getShippingAddress());
    }

    public function testDuplicatePreservesLineItemQuantity(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 500);
        $lineItem->setQuantity(7.5);

        $order = new Order();
        ReflectionUtil::setId($order, 4600);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals(7.5, $duplicatedOrder->getLineItems()->first()->getQuantity());
    }

    public function testDuplicatePreservesLineItemCurrency(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 600);
        $lineItem->setCurrency('GBP');

        $order = new Order();
        ReflectionUtil::setId($order, 4700);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertEquals('GBP', $duplicatedOrder->getLineItems()->first()->getCurrency());
    }

    public function testDuplicateWithLineItemHavingKitItems(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 400);

        $kitItem1 = new ProductKitItemStub(500);

        $kitItemProduct1 = new Product();
        ReflectionUtil::setId($kitItemProduct1, 600);

        $kitItem2 = new ProductKitItemStub(700);

        $kitItemProduct2 = new Product();
        ReflectionUtil::setId($kitItemProduct2, 800);

        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $kitItemLineItem1->setKitItem($kitItem1);
        $kitItemLineItem1->setProduct($kitItemProduct1);
        $kitItemLineItem1->setQuantity(2.0);

        $kitItemLineItem2 = new OrderProductKitItemLineItem();
        $kitItemLineItem2->setKitItem($kitItem2);
        $kitItemLineItem2->setProduct($kitItemProduct2);
        $kitItemLineItem2->setQuantity(3.0);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 900);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(1.0);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $lineItem->addKitItemLineItem($kitItemLineItem2);

        $order = new Order();
        ReflectionUtil::setId($order, 4800);
        $order->addLineItem($lineItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnArgument(0);

        $duplicatedOrder = $this->duplicator->duplicate($order);

        self::assertCount(1, $duplicatedOrder->getLineItems());

        $duplicatedLineItem = $duplicatedOrder->getLineItems()->first();
        self::assertNotSame($lineItem, $duplicatedLineItem);
        self::assertSame($product, $duplicatedLineItem->getProduct());

        self::assertCount(2, $duplicatedLineItem->getKitItemLineItems());

        $duplicatedKitItems = $duplicatedLineItem->getKitItemLineItems()->toArray();

        $kitItem1Id = $kitItemLineItem1->getKitItemId();
        self::assertNotSame($kitItemLineItem1, $duplicatedKitItems[$kitItem1Id]);
        self::assertSame($kitItem1, $duplicatedKitItems[$kitItem1Id]->getKitItem());
        self::assertSame($kitItemProduct1, $duplicatedKitItems[$kitItem1Id]->getProduct());
        self::assertEquals(2.0, $duplicatedKitItems[$kitItem1Id]->getQuantity());

        $kitItem2Id = $kitItemLineItem2->getKitItemId();
        self::assertNotSame($kitItemLineItem2, $duplicatedKitItems[$kitItem2Id]);
        self::assertSame($kitItem2, $duplicatedKitItems[$kitItem2Id]->getKitItem());
        self::assertSame($kitItemProduct2, $duplicatedKitItems[$kitItem2Id]->getProduct());
        self::assertEquals(3.0, $duplicatedKitItems[$kitItem2Id]->getQuantity());
    }
}
