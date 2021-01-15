<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCustomerOrderLineItemsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /** @var Product[]|array */
    protected $products;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerOrderDemoData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class,
        ];
    }

    /**
     * @param EntityManagerInterface $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $totalHelper = $this->getTotalHelper();
        $orders = $manager->getRepository(Order::class)->findAll();

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($order->getLineItems()->count()) {
                continue;
            }
            $order->setCurrency(CurrencyConfiguration::DEFAULT_CURRENCY);
            $productsCount = random_int(1, 5);

            for ($i = 0; $i < $productsCount; $i++) {
                $lineItem = $this->getOrderLineItem($manager);
                $order->addLineItem($lineItem);
            }
            $totalHelper->fillDiscounts($order);
            $totalHelper->fillSubtotals($order);
            $totalHelper->fillTotal($order);

            $manager->persist($order);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return OrderLineItem
     */
    private function getOrderLineItem(ObjectManager $manager)
    {
        $orderLineItem = new OrderLineItem();

        $quantity = random_int(1, 13);
        $price = Price::create(random_int(10, 1000), CurrencyConfiguration::DEFAULT_CURRENCY);
        $product = $this->getProduct($manager);

        $orderLineItem
            ->setFromExternalSource(random_int(0, 1))
            ->setProduct($product)
            ->setProductName($product->getName())
            ->setProductUnit($this->getProductUnit($product))
            ->setQuantity($quantity)
            ->setPrice($price);

        return $orderLineItem;
    }

    /**
     * @param ObjectManager $manager
     * @return Product
     */
    protected function getProduct(ObjectManager $manager)
    {
        if (null === $this->products) {
            $this->products = $manager->getRepository(Product::class)->findAll();
        }

        return $this->products[array_rand($this->products)];
    }

    /**
     * @param Product $product
     * @return ProductUnit|null
     */
    protected function getProductUnit(Product $product)
    {
        $units = $product->getAvailableUnits();

        return $units ? $units[array_rand($units)] : null;
    }

    /**
     * @return TotalHelper
     */
    protected function getTotalHelper()
    {
        return $this->container->get('oro_order.order.total.total_helper');
    }
}
