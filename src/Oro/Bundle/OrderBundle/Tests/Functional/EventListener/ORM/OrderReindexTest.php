<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;

/**
 * @dbIsolationPerTest
 */
class OrderReindexTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;
    use MessageQueueAssertTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->loadFixtures([
            LoadOrders::class,
            LoadOrderLineItemData::class,
            LoadProductData::class
        ]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_order.enable_purchase_history', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_order.enable_purchase_history', false);
        $configManager->flush();

        parent::tearDown();
    }

    public function testReindexWhenOrderChangeStatusIsApplicable(): void
    {
        /** get ORDER_4 because it has 2 product PRODUCT_1 and PRODUCT_6 */
        $order = $this->getReference(LoadOrders::ORDER_5);
        $this->changeOrderStatus($order, OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED);

        $messages = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());

        $expectedMessage = $this->getExpectedMessage($order);

        //Combination of entityIds [PRODUCT_1, PRODUCT_6] for this test
        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexWhenOrderChangeStatusNotApplicable(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_5);
        $this->changeOrderStatus($order, OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED);

        $messages = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());

        $expectedMessage = $this->getExpectedMessage($order);

        $this->assertNotContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenCreate(): void
    {
        $lineItem = $this->createOrderLineItem(
            LoadProductData::PRODUCT_7,
            10,
            LoadProductUnits::LITER,
            ['value' => 100, 'currency' => 'USD'],
            LoadOrders::ORDER_5
        );

        $messages = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());

        $expectedMessage = $this->getExpectedMessageForLineItem($lineItem->getProduct());

        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenDelete(): void
    {
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_6);
        $product = $lineItem->getProduct();

        $expectedMessage = $this->getExpectedMessageForLineItem($product);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->remove($lineItem);
        $em->flush();

        $messages = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());

        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenUpdate(): void
    {
        $this->clearMessageCollector();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_6);
        $product6 = $this->getReference(LoadProductData::PRODUCT_6);
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);

        $expectedMessage = $this->getExpectedMessageForLineItem($product6, $product8);

        $lineItem->setProduct($product8);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        $messages = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());

        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenEventFieldNotChanged(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_6);
        $lineItem->setCurrency('EUR');

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        $expectedMessage = $this->getExpectedMessageForLineItem($lineItem->getProduct());
        $messages = self::getSentMessagesByTopic(WebsiteSearchReindexTopic::getName());

        $this->assertNotContains($expectedMessage, $messages);
    }

    /**
     * @param Product[]|string[] $products
     *
     * @return array
     */
    private function getExpectedMessageForLineItem(...$products): array
    {
        $productsIds = array_map(function ($product) {
            return ($product instanceof Product) ? $product->getId() : $this->getReference($product)->getId();
        }, $products);

        return [
            'class' => [Product::class],
            'granulize' => true,
            'context' => [
                'websiteIds' => [$this->getDefaultWebsiteId()],
                'entityIds' => $productsIds,
                'fieldGroups' => ['order']
            ],
        ];
    }

    private function getExpectedMessage(Order $order): array
    {
        $productIds = [];
        foreach ($order->getProductsFromLineItems() as $product) {
            $productIds[] = $product->getId();
        }

        return [
            'class' => [Product::class],
            'granulize' => true,
            'context' => [
                'websiteIds' => [$this->getDefaultWebsiteId()],
                'entityIds' => $productIds,
                'fieldGroups' => ['order']
            ],
        ];
    }

    private function changeOrderStatus(Order $order, string $statusId): void
    {
        $order->setInternalStatus($this->getOrderInternalStatusById($statusId));
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();
    }

    private function getOrderInternalStatusById(string $id): EnumOptionInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(EnumOption::class)
            ->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId(Order::INTERNAL_STATUS_CODE, $id));
    }

    private function createOrderLineItem(
        string $product,
        int|float $qty,
        string $productUnit,
        array $price,
        string $order
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($this->getReference($product))
            ->setQuantity($qty)
            ->setProductUnit($this->getReference($productUnit))
            ->setPrice(Price::create($price['value'], $price['currency']));

        /* @var Order $order */
        $order = $this->getReference($order);
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        return $lineItem;
    }
}
