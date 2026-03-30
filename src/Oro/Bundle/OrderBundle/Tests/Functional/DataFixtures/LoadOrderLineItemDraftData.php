<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadOrderLineItemDraftData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string ORDER_LINE_ITEM_1 = 'order_line_item_1';
    public const string ORDER_LINE_ITEM_DRAFT_1 = 'order_line_item_draft_1';
    public const string ORDER_LINE_ITEM_2 = 'order_line_item_2';
    public const string ORDER_LINE_ITEM_3 = 'order_line_item_3';

    private static array $lineItems = [
        self::ORDER_LINE_ITEM_1 => [
            'order' => LoadOrders::ORDER_1,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 10,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD',
            ],
            'isDraft' => false,
        ],
        self::ORDER_LINE_ITEM_DRAFT_1 => [
            'order' => LoadOrders::ORDER_1,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 20,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 200,
                'currency' => 'USD',
            ],
            'draftSource' => self::ORDER_LINE_ITEM_1,
            'isDraft' => true,
        ],
        self::ORDER_LINE_ITEM_2 => [
            'order' => LoadOrders::ORDER_2,
            'product' => LoadProductData::PRODUCT_2,
            'quantity' => 5,
            'productUnit' => LoadProductUnits::BOTTLE,
            'price' => [
                'value' => 50,
                'currency' => 'USD',
            ],
            'isDraft' => false,
        ],
        self::ORDER_LINE_ITEM_3 => [
            'order' => LoadOrders::ORDER_2,
            'product' => LoadProductData::PRODUCT_2,
            'quantity' => 10,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 50,
                'currency' => 'USD',
            ],
            'isDraft' => true,
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrders::class,
            LoadProductUnitPrecisions::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // First pass: create non-draft line items
        foreach (self::$lineItems as $reference => $data) {
            if ($data['isDraft']) {
                continue;
            }

            $orderLineItem = $this->createLineItem($data);

            /** @var Order $order */
            $order = $this->getReference($data['order']);
            $order->addLineItem($orderLineItem);

            $this->setReference($reference, $orderLineItem);
            $manager->persist($orderLineItem);
        }

        $manager->flush();

        $orderDrafts = [];

        // Second pass: create draft line items
        foreach (self::$lineItems as $reference => $data) {
            if (!$data['isDraft']) {
                continue;
            }

            /** @var Order $order */
            $order = $this->getReference($data['order']);
            $orderDraft = $orderDrafts[$order->getId()] ?? null;
            if (!$orderDraft) {
                $orderDraft = $this->container->get('oro_order.draft_session.factory.order')
                    ->createDraft($order, UUIDGenerator::v4());
                $this->setReference($data['order'] . '_DRAFT', $orderDraft);
                $manager->persist($orderDraft);

                $orderDrafts[$order->getId()] = $orderDraft;
            }

            $orderLineItemDraft = $this->createLineItem($data);
            $orderLineItemDraft->setDraftSessionUuid($orderDraft->getDraftSessionUuid());

            if (isset($data['draftSource'])) {
                /** @var OrderLineItem $draftSource */
                $draftSource = $this->getReference($data['draftSource']);
                $orderLineItemDraft->setDraftSource($draftSource);
            }

            $orderDraft->addLineItem($orderLineItemDraft);

            $this->setReference($reference, $orderLineItemDraft);

            $manager->persist($orderLineItemDraft);
        }

        $manager->flush();
    }

    private function createLineItem(array $data): OrderLineItem
    {
        $entity = new OrderLineItem();

        /** @var Product $product */
        $product = $this->getReference($data['product']);
        $entity->setProduct($product);

        $entity->setProductSku($product->getSku());
        $entity->setQuantity($data['quantity']);

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference($data['productUnit']);
        $entity->setProductUnit($productUnit);

        $entity->setPrice(Price::create($data['price']['value'], $data['price']['currency']));
        $entity->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);

        return $entity;
    }
}
