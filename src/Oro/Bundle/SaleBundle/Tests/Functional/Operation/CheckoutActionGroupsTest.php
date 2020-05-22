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
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutActionGroupsTest extends FrontendActionTestCase
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::LEVEL_1_EMAIL, LoadCustomerUserData::LEVEL_1_PASSWORD)
        );
        $this->loadFixtures(
            [
                LoadCustomerUserData::class,
                LoadQuoteCheckoutsData::class,
            ]
        );
    }

    public function testUpdateBillingAddressWithoutRrequiredParameters()
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
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $checkout->setShipToBillingAddress(true);

        $billingAddress = $this->createEntity(OrderAddress::class, ['phone' => '123']);
        $shippingAddress = $this->createEntity(OrderAddress::class, ['phone' => '234']);
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
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $checkout->setShipToBillingAddress(true);

        $billingAddress = $this->createEntity(OrderAddress::class, ['phone' => '123']);
        $shippingAddress = $this->createEntity(OrderAddress::class, ['phone' => '234']);
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
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $checkout->setShipToBillingAddress(true);

        $billingAddress = $this->createEntity(OrderAddress::class, ['phone' => '123']);
        $shippingAddress = $this->createEntity(OrderAddress::class, ['phone' => '234']);
        $shippingAddressId = $shippingAddress->getId();

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $result = $this->executeActionGroup(
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

    public function testUpdateShippingMethodWithoutRrequiredParameters()
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
        /* @var $checkout Checkout */
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
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $billingAddress = $this->createEntity(OrderAddress::class, ['phone' => '123']);
        $shippingAddress = $this->createEntity(OrderAddress::class, ['phone' => '234']);

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $result = $this->executeActionGroup(
            'b2b_flow_checkout_place_order',
            [
                'checkout' => $checkout,
            ]
        );

        $this->assertArrayHasKey('order', $result);

        /* @var $order Order */
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

    public function testPurchaseWithoutRrequiredParameters()
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
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $billingAddress = $this->createEntity(OrderAddress::class, ['phone' => '123']);
        $shippingAddress = $this->createEntity(OrderAddress::class, ['phone' => '234']);

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $placeOrderResult = $this->executeActionGroup('b2b_flow_checkout_place_order', ['checkout' => $checkout]);
        /* @var $order Order */
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

    public function testFinishCheckoutWithoutRrequiredParameters()
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
        /* @var $checkout Checkout */
        $checkout = $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1);

        $billingAddress = $this->createEntity(OrderAddress::class, ['phone' => '123']);
        $shippingAddress = $this->createEntity(OrderAddress::class, ['phone' => '234']);

        $checkout->setBillingAddress($billingAddress)->setShippingAddress($shippingAddress);

        $placeOrderResult = $this->executeActionGroup('b2b_flow_checkout_place_order', ['checkout' => $checkout]);
        /* @var $order Order */
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

    /**
     * @param Order $order
     * @return float
     */
    protected function getOrderSubtotal(Order $order)
    {
        $total = 0;

        foreach ($order->getLineItems() as $lineItem) {
            $total += $lineItem->getQuantity() * $lineItem->getPrice()->getValue();
        }

        return $total;
    }

    /**
     * @param string $class
     * @param array $properties
     * @return object
     */
    protected function createEntity($class, array $properties)
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($class);

        $entity = $this->getEntity($class, $properties);

        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * @param string $class
     * @param mixed $id
     * @return object|null
     */
    protected function findEntity($class, $id)
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($class);

        return $em->find($class, $id);
    }

    /**
     * @param object|string $entity
     * @param array $excludeProps
     * @return array
     */
    protected function entityToArray($entity, array $excludeProps = [])
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
