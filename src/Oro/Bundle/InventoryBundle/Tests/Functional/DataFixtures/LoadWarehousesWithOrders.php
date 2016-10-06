<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class LoadWarehousesWithOrders extends AbstractFixture implements DependentFixtureInterface
{
    const WAREHOUSE1 = 'warehouse.1';
    const WAREHOUSE2 = 'warehouse.2';
    const ORDER_LINE_ITEM_1 = 'orderlineitem.1';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWarehousesAndInventoryLevels::class,
            LoadOrders::class,
            LoadProductData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Order $order1 */
        $order1 = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);

        $price = Price::create(1, 'EUR');
        $price->setValue(1);

        $productUnit = new ProductUnit();
        $productUnit->setCode('xxx')->setDefaultPrecision(1);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setProduct($product1)
            ->setUnit($productUnit)
            ->setPrecision(1);

        $product1->addUnitPrecision($unitPrecision);

        $orderLineItem = new OrderLineItem();
        $orderLineItem
            ->setPrice($price)
            ->setQuantity(1)
            ->setProduct($product1)
            ->setProductUnit($productUnit);
        $order1->addLineItem($orderLineItem);
        $orderLineItem->setOrder($order1);

        $warehouse1 = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);
        $order1->setWarehouse($warehouse1);

        $manager->persist($orderLineItem);
        $manager->persist($productUnit);
        $manager->persist($unitPrecision);
        $manager->flush();
    }
}
