<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Operation;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutActionGroupsTest extends FrontendActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::LEVEL_1_EMAIL, LoadCustomerUserData::LEVEL_1_PASSWORD)
        );
        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadQuoteCheckoutsData::class,
        ]);
    }

    public function testUpdateBillingAddressWithoutRequiredParameters()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Trying to execute ActionGroup "b2b_flow_checkout_update_billing_address" '
            . 'with invalid or missing parameter(s): "checkout"'
        );

        $this->executeActionGroup('b2b_flow_checkout_update_billing_address');
    }

    public function testUpdateBillingAddress()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $checkout->setShipToBillingAddress(true);

        $billingAddress = $this->createOrderAddress('123');
        $shippingAddress = $this->createOrderAddress('234');
        $shippingAddressId = $shippingAddress->getId();

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $result = $this->executeActionGroup(
            'b2b_flow_checkout_update_billing_address',
            [
                'checkout' => $checkout,
            ]
        );

        $this->assertTrue($result['billing_address_has_shipping']);

        // shipping address are duplicated, old shipping address removed
        $this->assertNull($this->findEntity(OrderAddress::class, $shippingAddressId));
        $this->assertEquals(
            $this->entityToArray($checkout->getBillingAddress(), ['id', 'created', 'updated']),
            $this->entityToArray($checkout->getShippingAddress(), ['id', 'created', 'updated'])
        );
        $this->assertNotSame($shippingAddress, $checkout->getShippingAddress());
        $this->assertSame($billingAddress, $checkout->getBillingAddress());
    }

    public function testUpdateBillingAddressWithDisallowShippingAddressEdit()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $checkout->setShipToBillingAddress(true);

        $billingAddress = $this->createOrderAddress('123');
        $shippingAddress = $this->createOrderAddress('234');
        $shippingAddressId = $shippingAddress->getId();

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $result = $this->executeActionGroup(
            'b2b_flow_checkout_update_billing_address',
            [
                'checkout' => $checkout,
                'disallow_shipping_address_edit' => true,
            ]
        );

        $this->assertTrue($result['billing_address_has_shipping']);

        // shipping address doesn't duplicated
        $this->assertNotNull($this->findEntity(OrderAddress::class, $shippingAddressId));
        $this->assertNotEquals(
            $this->entityToArray($checkout->getBillingAddress(), ['id', 'created', 'updated']),
            $this->entityToArray($checkout->getShippingAddress(), ['id', 'created', 'updated'])
        );
        $this->assertSame($shippingAddress, $checkout->getShippingAddress());
        $this->assertSame($billingAddress, $checkout->getBillingAddress());
    }

    public function testUpdateShippingAddressWithoutRequiredAttributes()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Trying to execute ActionGroup "b2b_flow_checkout_update_shipping_address" '
            . 'with invalid or missing parameter(s): "checkout"'
        );

        $this->executeActionGroup('b2b_flow_checkout_update_shipping_address');
    }

    public function testUpdateShippingAddress()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $checkout->setShipToBillingAddress(true);

        $billingAddress = $this->createOrderAddress('123');
        $shippingAddress = $this->createOrderAddress('234');
        $shippingAddressId = $shippingAddress->getId();

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $this->executeActionGroup(
            'b2b_flow_checkout_update_shipping_address',
            [
                'checkout' => $checkout,
            ]
        );

        // shipping address are duplicated, old shipping address removed
        $this->assertNull($this->findEntity(OrderAddress::class, $shippingAddressId));
        $this->assertEquals(
            $this->entityToArray($checkout->getBillingAddress(), ['id', 'created', 'updated']),
            $this->entityToArray($checkout->getShippingAddress(), ['id', 'created', 'updated'])
        );
        $this->assertNotSame($shippingAddress, $checkout->getShippingAddress());
        $this->assertSame($billingAddress, $checkout->getBillingAddress());
    }

    public function testUpdateShippingMethodWithoutRequiredParameters()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Trying to execute ActionGroup "b2b_flow_checkout_update_shipping_method" '
            . 'with invalid or missing parameter(s): "checkout"'
        );

        $this->executeActionGroup('b2b_flow_checkout_update_shipping_method');
    }

    public function testUpdateShippingMethod()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $shippingCost = $checkout->getShippingCost();

        $this->executeActionGroup(
            'b2b_flow_checkout_update_shipping_method',
            [
                'checkout' => $checkout,
            ]
        );

        $this->assertNotSame($shippingCost, $checkout->getShippingCost());
    }

    public function testPlaceOrderWithoutRequiredAttributes()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Trying to execute ActionGroup "b2b_flow_checkout_place_order" ' .
            'with invalid or missing parameter(s): "checkout"'
        );

        $this->executeActionGroup('b2b_flow_checkout_place_order');
    }

    public function testPlaceOrder()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $billingAddress = $this->createOrderAddress('123');
        $shippingAddress = $this->createOrderAddress('234');

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $result = $this->executeActionGroup(
            'b2b_flow_checkout_place_order',
            [
                'checkout' => $checkout,
            ]
        );

        $this->assertArrayHasKey('order', $result);

        /* @var Order $order */
        $order = $result['order'];

        $this->assertNotNull($order);
        $this->assertNotNull($order->getId());
        $this->assertEquals(Quote::class, $order->getSourceEntityClass());
        $this->assertSameSize($checkout->getLineItems(), $order->getLineItems());
        $this->assertEquals($this->getOrderSubtotal($order), $order->getSubtotal());

        $this->assertNotNull($order->getShippingAddress());
        $this->assertNotNull($order->getShippingAddress()->getId());
        $this->assertNotSame($shippingAddress, $order->getShippingAddress());
        $this->assertEquals(
            $this->entityToArray($shippingAddress, ['id', 'created', 'updated']),
            $this->entityToArray($order->getShippingAddress(), ['id', 'created', 'updated'])
        );

        $this->assertNotNull($order->getBillingAddress());
        $this->assertNotNull($order->getBillingAddress()->getId());
        $this->assertNotSame($billingAddress, $order->getBillingAddress());
        $this->assertEquals(
            $this->entityToArray($billingAddress, ['id', 'created', 'updated']),
            $this->entityToArray($order->getBillingAddress(), ['id', 'created', 'updated'])
        );
    }

    public function testPurchaseWithoutRequiredParameters()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Trying to execute ActionGroup "b2b_flow_checkout_purchase" '
            . 'with invalid or missing parameter(s): "checkout", "order"'
        );

        $this->executeActionGroup('b2b_flow_checkout_purchase');
    }

    public function testPurchaseOrder()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $billingAddress = $this->createOrderAddress('123');
        $shippingAddress = $this->createOrderAddress('234');

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $placeOrderResult = $this->executeActionGroup('b2b_flow_checkout_place_order', ['checkout' => $checkout]);
        /* @var Order $order */
        $order = $placeOrderResult['order'];

        $result = $this->executeActionGroup(
            'b2b_flow_checkout_purchase',
            [
                'checkout' => $checkout,
                'order' => $order,
                'transactionOptions' => ['option1' => 'value1'],
            ]
        );

        $responseData = $result->get('responseData');
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('successUrl', $responseData);
        $this->assertArrayHasKey('returnUrl', $responseData);
        $this->assertArrayHasKey('errorUrl', $responseData);
        $this->assertArrayHasKey('failureUrl', $responseData);
        $this->assertArrayHasKey('failedShippingAddressUrl', $responseData);
        $this->assertArrayHasKey('option1', $responseData);
        $this->assertEquals('value1', $responseData['option1']);
    }

    public function testFinishCheckoutWithoutRequiredParameters()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Trying to execute ActionGroup "b2b_flow_checkout_finish_checkout" '
            . 'with invalid or missing parameter(s): "checkout", "order"'
        );

        $this->executeActionGroup('b2b_flow_checkout_finish_checkout');
    }

    public function testFinishCheckout()
    {
        /* @var Checkout $checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $billingAddress = $this->createOrderAddress('123');
        $shippingAddress = $this->createOrderAddress('234');

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $placeOrderResult = $this->executeActionGroup('b2b_flow_checkout_place_order', ['checkout' => $checkout]);
        /* @var Order $order */
        $order = $placeOrderResult['order'];

        $checkoutSourceClass = get_class($checkout->getSourceEntity());
        $checkoutSourceId = $checkout->getSourceEntity()->getId();

        $this->assertFalse($checkout->isCompleted());

        $this->executeActionGroup(
            'b2b_flow_checkout_finish_checkout',
            [
                'checkout' => $checkout,
                'order' => $order,
                'auto_remove_source' => true,
            ]
        );

        $this->assertTrue($checkout->isCompleted());

        // check completedData
        $completedData = $checkout->getCompletedData();
        $this->assertEquals(count($order->getLineItems()), $completedData->getItemsCount());
        $this->assertEquals($order->getCurrency(), $completedData->getCurrency());
        $this->assertEquals($order->getSubtotal(), $completedData->getSubtotal());
        $this->assertEquals($order->getTotal(), $completedData->getTotal());
        $this->assertEquals(
            [
                [
                    'entityAlias' => 'order',
                    'entityId' => ['id' => $order->getId()],
                ],
            ],
            $completedData['orders']
        );
        $this->assertNull($checkout->getSourceEntity());

        // check remove source
        $this->getContainer()->get('doctrine')->getManagerForClass($checkoutSourceClass)->flush();
        $this->assertNull($this->findEntity($checkoutSourceClass, $checkoutSourceId));

        $this->getContainer()->get('oro_entity_config.config_manager')->clear();
    }

    private function getOrderSubtotal(Order $order): float
    {
        $total = 0;
        foreach ($order->getLineItems() as $lineItem) {
            $total += $lineItem->getQuantity() * $lineItem->getPrice()->getValue();
        }

        return $total;
    }

    private function createOrderAddress(string $phone): OrderAddress
    {
        $entity = new OrderAddress();
        $entity->setPhone($phone);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(OrderAddress::class);
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    private function findEntity(string $class, mixed $id): ?object
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class)->find($class, $id);
    }

    private function entityToArray(object|string $entity, array $excludeProps = []): array
    {
        $data = [];

        $reflection = new \ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $data[$property->getName()] = $property->getValue($entity);
        }

        foreach ($excludeProps as $prop) {
            unset($data[$prop]);
        }

        return $data;
    }
}
