<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

class LoadOrderLineItemData extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_LINEITEM_2 = 'order_lineitem_2';
    const ORDER_LINEITEM_3 = 'order_lineitem_3';
    const ORDER_LINEITEM_4 = 'order_lineitem_4';
    const ORDER_LINEITEM_5 = 'order_lineitem_5';
    const ORDER_LINEITEM_6 = 'order_lineitem_6';
    const ORDER_LINEITEM_7 = 'order_lineitem_7';
    const ORDER_LINEITEM_8 = 'order_lineitem_8';
    const ORDER_LINEITEM_9 = 'order_lineitem_9';

    /**
     * @var array
     */
    protected $lineItems = [
        self::ORDER_LINEITEM_2 => [
            'order' => LoadOrders::ORDER_2,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 10,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_3 => [
            'order' => LoadOrders::ORDER_3,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 15,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_4 => [
            'order' => LoadOrders::ORDER_4,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 20,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_5 => [
            'order' => LoadOrders::ORDER_5,
            'product' => LoadProductData::PRODUCT_5,
            'quantity' => 20,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_6 => [
            'order' => LoadOrders::ORDER_5,
            'product' => LoadProductData::PRODUCT_6,
            'quantity' => 20,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 200,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_7 => [
            'order' => LoadOrders::ORDER_6,
            'product' => LoadProductData::PRODUCT_5,
            'quantity' => 20,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_8 => [
            'order' => LoadOrders::ORDER_6,
            'product' => LoadProductData::PRODUCT_6,
            'quantity' => 20,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 200,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_9 => [
            'order' => LoadOrders::ORDER_6,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 15,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 100,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrders::class,
            LoadProductUnitPrecisions::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->lineItems as $name => $definition) {
            $lineItem = new OrderLineItem();
            $lineItem->setProduct($this->getReference($definition['product']))
                ->setQuantity($definition['quantity'])
                ->setProductUnit($this->getReference($definition['productUnit']))
                ->setPrice(Price::create($definition['price']['value'], $definition['price']['currency']));

            /* @var Order $order */
            $order = $this->getReference($definition['order']);
            $order->addLineItem($lineItem);

            $this->addReference($name, $lineItem);

            $manager->persist($lineItem);
        }

        $manager->flush();
    }
}
