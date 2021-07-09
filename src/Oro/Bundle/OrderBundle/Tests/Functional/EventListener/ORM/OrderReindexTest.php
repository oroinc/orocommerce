<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\EventListener\ORM;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
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
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @dbIsolationPerTest
 */
class OrderReindexTest extends FrontendWebTestCase
{
    use MessageQueueAssertTrait;
    use PreviouslyPurchasedFeatureTrait;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        $this->managerRegistry = $this->getContainer()->get('doctrine');

        $this->loadFixtures([
            LoadOrders::class,
            LoadOrderLineItemData::class,
            LoadProductData::class
        ]);

        $this->enablePreviouslyPurchasedFeature();
    }

    public function testReindexWhenOrderChangeStatusIsApplicable()
    {
        /** get ORDER_4 because it has 2 product PRODUCT_1 and PRODUCT_6  */
        $order = $this->getReference(LoadOrders::ORDER_5);
        $this->changeOrderStatus($order, OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED);

        $messages = self::getSentMessagesByTopic(AsyncIndexer::TOPIC_REINDEX, true);

        $expectedMessage = $this->getExpectedMessage($order);

        //Combination of entityIds [PRODUCT_1, PRODUCT_6] for this test
        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexWhenOrderChangeStatusNotApplicable()
    {
        $order = $this->getReference(LoadOrders::ORDER_5);
        $this->changeOrderStatus($order, OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED);

        $messages = self::getSentMessagesByTopic(AsyncIndexer::TOPIC_REINDEX, true);

        $expectedMessage = $this->getExpectedMessage($order);

        $this->assertNotContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenCreate()
    {
        $lineItem = $this->createOrderLineItem(
            LoadProductData::PRODUCT_7,
            10,
            LoadProductUnits::LITER,
            ['value' => 100, 'currency' => 'USD'],
            LoadOrders::ORDER_5
        );

        $messages = self::getSentMessagesByTopic(AsyncIndexer::TOPIC_REINDEX, true);

        $expectedMessage = $this->getExpectedMessageForLineItem($lineItem->getProduct());

        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenDelete()
    {
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_6);
        $product = $lineItem->getProduct();

        $expectedMessage = $this->getExpectedMessageForLineItem($product);

        $em = $this->managerRegistry->getManagerForClass(OrderLineItem::class);
        $em->remove($lineItem);
        $em->flush();

        $messages = self::getSentMessagesByTopic(AsyncIndexer::TOPIC_REINDEX, true);

        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenUpdate()
    {
        $this->clearMessageCollector();

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_6);
        $product6 = $this->getReference(LoadProductData::PRODUCT_6);
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);

        $expectedMessage = $this->getExpectedMessageForLineItem($product6, $product8);

        $lineItem->setProduct($product8);

        $em = $this->managerRegistry->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        $messages = self::getSentMessagesByTopic(AsyncIndexer::TOPIC_REINDEX, true);

        $this->assertContains($expectedMessage, $messages);
    }

    public function testReindexProductLineItemWhenEventFieldNotChanged()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItemData::ORDER_LINEITEM_6);
        $lineItem->setCurrency('EUR');

        $em = $this->managerRegistry->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        $expectedMessage = $this->getExpectedMessageForLineItem($lineItem->getProduct());
        $messages = self::getSentMessagesByTopic(AsyncIndexer::TOPIC_REINDEX, true);

        $this->assertNotContains($expectedMessage, $messages);
    }

    /**
     * @param Product[]|string[] $products
     * @return array
     */
    protected function getExpectedMessageForLineItem(...$products): array
    {
        $productsIds = array_map(function ($product) {
            return ($product instanceof Product) ? $product->getId() : $this->getReference($product)->getId();
        }, $products);

        return [
            'class' => [Product::class],
            'granulize' => true,
            'context' => [
                'websiteIds' => [$this->getDefaultWebsiteId()],
                'entityIds' => $productsIds
            ],
        ];
    }

    protected function getExpectedMessage(Order $order)
    {
        $productIds = [];

        foreach ($order->getProductsFromLineItems() as $product) {
            array_push($productIds, $product->getId());
        }

        return [
            'class' => [Product::class],
            'granulize' => true,
            'context' => [
                'websiteIds' => [$this->getDefaultWebsiteId()],
                'entityIds' => $productIds,
            ],
        ];
    }

    /**
     * @param Order  $order
     * @param string $statusId
     */
    protected function changeOrderStatus(Order $order, $statusId)
    {
        $order->setInternalStatus($this->getOrderInternalStatusById($statusId));
        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();
    }

    /**
     * @param string $id
     *
     * @return object|AbstractEnumValue
     * @throws \InvalidArgumentException
     */
    protected function getOrderInternalStatusById($id = OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN)
    {
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $this->managerRegistry->getManagerForClass($className)->getRepository($className)->find($id);
    }

    /**
     * @param string    $product
     * @param int|float $qty
     * @param string    $productUnit
     * @param array     $price
     * @param string    $order
     *
     * @return OrderLineItem
     */
    protected function createOrderLineItem(
        $product,
        $qty,
        $productUnit,
        array $price,
        $order
    ) {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($this->getReference($product))
            ->setQuantity($qty)
            ->setProductUnit($this->getReference($productUnit))
            ->setPrice(Price::create($price['value'], $price['currency']));

        /* @var $order Order */
        $order = $this->getReference($order);
        $order->addLineItem($lineItem);

        $em = $this->managerRegistry->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        return $lineItem;
    }
}
