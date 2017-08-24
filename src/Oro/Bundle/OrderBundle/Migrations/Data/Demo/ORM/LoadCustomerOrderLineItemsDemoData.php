<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class LoadCustomerOrderLineItemsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

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
        /** @var RateConverterInterface $rateConverter */
        $rateConverter = $this->container->get('oro_currency.converter.rate');

        $product = $manager->getRepository(Product::class)->findOneBy([]);
        $productUnit = $manager->getRepository(ProductUnit::class)->findOneBy([]);

        $orders = $manager->getRepository(Order::class)->findAll();

        /** @var Order $order */
        foreach ($orders as $order) {
            if ($order->getLineItems()->count()) {
                continue;
            }

            $orderLineItem = $this->getOrderLineItem($product, $productUnit);

            $totalValue = $orderLineItem->getQuantity() * $orderLineItem->getPrice()->getValue();

            $total = MultiCurrency::create($totalValue, $orderLineItem->getCurrency());
            $baseTotal = $rateConverter->getBaseCurrencyAmount($total);
            $total->setBaseCurrencyValue($baseTotal);

            $subtotal = MultiCurrency::create($totalValue, $orderLineItem->getCurrency());
            $baseSubtotal = $rateConverter->getBaseCurrencyAmount($subtotal);
            $subtotal->setBaseCurrencyValue($baseSubtotal);

            $orderLineItem->setOrder($order);
            $manager->persist($orderLineItem);

            $order
                ->addLineItem($orderLineItem)
                ->setCurrency(CurrencyConfiguration::DEFAULT_CURRENCY)
                ->setTotalObject($total)
                ->setSubtotalObject($subtotal);
        }

        $manager->flush();
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     *
     * @return OrderLineItem
     */
    private function getOrderLineItem(Product $product, ProductUnit $productUnit)
    {
        $orderLineItem = new OrderLineItem();

        $quantity = random_int(1, 13);
        $price = Price::create(random_int(10, 1000), CurrencyConfiguration::DEFAULT_CURRENCY);
        $priceTypes = [OrderLineItem::PRICE_TYPE_UNIT, OrderLineItem::PRICE_TYPE_BUNDLED];

        $orderLineItem
            ->setFromExternalSource(random_int(0, 1))
            ->setProduct($product)
            ->setFreeFormProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPriceType($priceTypes[array_rand($priceTypes)])
            ->setPrice($price);

        return $orderLineItem;
    }
}
