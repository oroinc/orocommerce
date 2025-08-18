<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Operation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

abstract class OrderFrontendOperationsTestCase extends FrontendActionTestCase
{
    use ConfigManagerAwareTestTrait;

    protected EntityManagerInterface $emProduct;
    protected EntityManagerInterface $emFallback;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $doctrine = self::getContainer()->get('doctrine');

        $this->emProduct = $doctrine->getManagerForClass(Product::class);
        $this->emFallback = $doctrine->getManagerForClass(EntityFieldFallbackValue::class);

        $this->loadFixtures($this->getFixtures());

        $this->initConfigs();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigs();
        parent::tearDown();
    }

    protected function initConfigs(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.manage_inventory', true);
        $configManager->flush();
    }

    protected function restoreConfigs(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.manage_inventory', false);
        $configManager->flush();
    }

    abstract protected function getFixtures(): array;

    public function testReorder(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_6);
        $orderLineItems = $order->getLineItems();
        $this->assertGreaterThan(1, count($orderLineItems));
        $lineItem = $orderLineItems->first();
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
        static::assertStringContainsString(
            sprintf('Order #%s', $order->getIdentifier()),
            trim($lastCheckoutData['startedFrom'])
        );
    }

    protected function startCheckout(Order $order): Checkout
    {
        $repository = $this->getCheckoutRepository();
        $allCheckoutsCnt = count($repository->findAll());
        $this->executeOperation($order, 'oro_checkout_frontend_start_from_order');

        $data = self::jsonToArray($this->client->getResponse()->getContent());
        $this->assertTrue($data['success']);
        /** @var Checkout[] $allCheckoutsAfterReorder */
        $allCheckoutsAfterReorder = $repository->findBy([], ['id' => 'DESC']);

        $this->assertCount($allCheckoutsCnt + 1, $allCheckoutsAfterReorder);
        $checkoutFromOrder = array_shift($allCheckoutsAfterReorder);
        /** @var Order $sourceEntity */
        $sourceEntity = $checkoutFromOrder->getSourceEntity();
        $this->assertInstanceOf(Order::class, $sourceEntity);
        $this->assertEquals($order->getId(), $sourceEntity->getId());

        return $checkoutFromOrder;
    }

    protected function getOpenOrdersGridData(): array
    {
        $this->client->request('GET', '/');
        /** @var Response $gridResponse */
        $gridResponse = $this->client->requestFrontendGrid(['gridName' => 'frontend-checkouts-grid']);

        return self::jsonToArray($gridResponse->getContent())['data'];
    }

    protected function getCheckoutRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Checkout::class);
    }

    protected function executeOperation(Order $order, string $operationName, int $statusCode = Response::HTTP_OK): void
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

    protected function setProductDecrement(Product $product, int $quantity): void
    {
        $entityFallback = $this->createFallbackEntity($quantity);
        $product->setDecrementQuantity($entityFallback);
        $this->emProduct->flush();
        $this->emFallback->flush();
    }

    protected function createFallbackEntity(mixed $scalarValue): EntityFieldFallbackValue
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($scalarValue);
        $this->emFallback->persist($entityFallback);

        return $entityFallback;
    }
}
