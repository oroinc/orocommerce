<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

class LoadConfigurableProductOrderLineItemData extends AbstractFixture implements DependentFixtureInterface
{
    public const ORDER_LINEITEM_WITH_PARENT_PRODUCT_1 = 'order_lineitem_with_parent_product_1';
    public const ORDER_LINEITEM_WITH_PARENT_PRODUCT_2 = 'order_lineitem_with_parent_product_2';

    /**
     * @var array
     */
    protected $lineItems = [
        self::ORDER_LINEITEM_WITH_PARENT_PRODUCT_1 => [
            'order' => LoadOrders::ORDER_2,
            'parentProduct' => LoadProductData::PRODUCT_2,
            'product' => LoadProductData::PRODUCT_1,
            'quantity' => 10,
            'productUnit' => LoadProductUnits::LITER,
            'price' => [
                'value' => 101,
                'currency' => 'USD'
            ],
            'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
        ],
        self::ORDER_LINEITEM_WITH_PARENT_PRODUCT_2 => [
            'order' => LoadOrders::ORDER_3,
            'parentProduct' => LoadProductData::PRODUCT_7,
            'product' => LoadProductData::PRODUCT_3,
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
            LoadOrderLineItemData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->lineItems as $name => $definition) {
            $lineItem = new OrderLineItem();
            $lineItem
                ->setParentProduct($this->getReference($definition['parentProduct']))
                ->setProduct($this->getReference($definition['product']))
                ->setQuantity($definition['quantity'])
                ->setProductUnit($this->getReference($definition['productUnit']))
                ->setPrice(Price::create($definition['price']['value'], $definition['price']['currency']));

            /* @var $order Order */
            $order = $this->getReference($definition['order']);
            $order->addLineItem($lineItem);

            $this->addReference($name, $lineItem);

            $manager->persist($lineItem);
        }

        $manager->flush();
    }
}
