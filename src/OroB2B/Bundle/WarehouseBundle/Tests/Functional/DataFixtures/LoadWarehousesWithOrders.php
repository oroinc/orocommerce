<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
