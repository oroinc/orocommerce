<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Operation;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

class OrderFrontendOperationsTest extends FrontendActionTestCase
{
    /** @var ObjectManager */
    protected $emProduct;

    /** @var ObjectManager */
    protected $emFallback;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $doctrine = $this->getContainer()->get('doctrine');

        $this->emProduct = $doctrine->getManagerForClass(Product::class);
        $this->emFallback = $doctrine->getManagerForClass(EntityFieldFallbackValue::class);

        $this->loadFixtures([
            LoadOrders::class,
            LoadOrderLineItemData::class,
        ]);
    }

    public function testReorder()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_5);
        $orderLineItems = $order->getLineItems();
        $this->assertGreaterThan(1, count($orderLineItems));
        $lineItem = $orderLineItems[0];
        // set unlimited count for the first line item
        $this->setProductDecrement($lineItem->getProduct(), 0);

        $checkoutsInGridCnt = count($this->getOpenOrdersGridData());

        $firstCheckout = $this->startCheckout($order);
        $this->assertCount(1, $firstCheckout->getLineItems());

        // from orders - always start new checkout
        $secondCheckout = $this->startCheckout($order);

        $this->assertNotEquals($firstCheckout->getId(), $secondCheckout->getId());

        $checkoutsInGridAfterReorder = $this->getOpenOrdersGridData();
        $this->assertCount($checkoutsInGridCnt + 2, $checkoutsInGridAfterReorder);
        $lastCheckoutData = array_pop($checkoutsInGridAfterReorder);
        $this->assertContains(
            sprintf('Order #%s', $order->getIdentifier()),
            trim($lastCheckoutData['startedFrom'])
        );
    }

    /**
     * @param Order $order
     * @return Checkout
     */
    protected function startCheckout(Order $order)
    {
        $repository = $this->getCheckoutRepository();
        $allCheckoutsCnt = count($repository->findAll());
        $this->executeOperation($order, 'oro_checkout_frontend_start_from_order');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        /** @var Checkout[] $allCheckoutsAfterReorder */
        $allCheckoutsAfterReorder = $repository->findAll();
        $allCheckoutsAfterReorderCnt = count($allCheckoutsAfterReorder);
        $this->assertEquals($allCheckoutsCnt + 1, $allCheckoutsAfterReorderCnt);
        $checkoutFromOrder = array_pop($allCheckoutsAfterReorder);
        /** @var Order $sourceEntity */
        $sourceEntity = $checkoutFromOrder->getSourceEntity();
        $this->assertInstanceOf(Order::class, $sourceEntity);
        $this->assertEquals($order->getId(), $sourceEntity->getId());

        return $checkoutFromOrder;
    }

    /**
     * @return array
     */
    public function getOpenOrdersGridData()
    {
        $this->client->request('GET', '/');
        $gridResponse = $this->client->requestFrontendGrid(['gridName' => 'frontend-checkouts-grid']);

        return json_decode($gridResponse->getContent(), true)['data'];
    }

    /**
     * @return ObjectRepository
     */
    public function getCheckoutRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Checkout::class)
            ->getRepository(Checkout::class);
    }

    /**
     * @param Order $order
     * @param string $operationName
     * @param int $statusCode
     */
    protected function executeOperation(Order $order, $operationName, $statusCode = Response::HTTP_OK)
    {
        $this->assertExecuteOperation(
            $operationName,
            $order->getId(),
            Order::class,
            ['route' => 'oro_order_frontend_view'],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            $statusCode
        );
    }

    /**
     * @param Product $product
     * @param int $quantity
     */
    protected function setProductDecrement(Product $product, $quantity)
    {
        $entityFallback = $this->createFallbackEntity($quantity);
        $product->setDecrementQuantity($entityFallback);
        $this->emProduct->flush();
        $this->emFallback->flush();
    }

    /**
     * @param mixed $scalarValue
     * @return EntityFieldFallbackValue
     */
    protected function createFallbackEntity($scalarValue)
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($scalarValue);
        $this->emFallback->persist($entityFallback);

        return $entityFallback;
    }
}
