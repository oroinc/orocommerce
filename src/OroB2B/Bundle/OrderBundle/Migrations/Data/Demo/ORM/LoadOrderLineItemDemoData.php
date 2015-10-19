<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadOrderLineItemDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ProductPriceProvider */
    protected $productPriceProvider;

    /** @var array|Order[] */
    protected $orders = [];

    /** @var array|Product[] */
    protected $products = [];

    /** @var array */
    protected $prices = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->productPriceProvider = $container->get('orob2b_pricing.provider.product_price');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM\LoadOrderDemoData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData'
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BOrderBundle/Migrations/Data/Demo/ORM/data/order-line-items.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $orderLineItem = new OrderLineItem();

            $order = $this->getOrder($manager, $row['orderIdentifier']);
            $order->addLineItem($orderLineItem);

            $product = $this->getProduct($manager, $row['productSku']);
            $productUnit = $this->getProductUnit($manager, $row['productUnitCode']);

            $quantity = 1;
            if (empty($row['freeFormProduct'])) {
                $quantity = mt_rand(1, 50);
            }

            $price = Price::create(mt_rand(10, 1000), $order->getCurrency());
            if ($product) {
                $price = $this->getPrice(
                    $product,
                    $productUnit,
                    $quantity,
                    $order->getCurrency(),
                    $order->getPriceList()
                );
            }

            $date = null;
            if (!empty($row['shipBy'])) {
                $date = new \DateTime($row['shipBy']);
            }

            $priceTypes = [OrderLineItem::PRICE_TYPE_UNIT, OrderLineItem::PRICE_TYPE_BUNDLED];

            $orderLineItem
                ->setFromExternalSource(mt_rand(0, 1))
                ->setProduct($product)
                ->setFreeFormProduct($product ? null : $row['freeFormProduct'])
                ->setProductUnit($productUnit)
                ->setQuantity($quantity)
                ->setPriceType($priceTypes[array_rand($priceTypes)])
                ->setPrice($price)
                ->setShipBy($date)
                ->setComment($row['comment']);

            $manager->persist($orderLineItem);
        }

        fclose($handler);

        $subtotalsProvider = $this->container->get('orob2b_order.provider.subtotals');
        foreach ($this->orders as $order) {
            $subtotals = $subtotalsProvider->getSubtotals($order);
            /** @var Subtotal $subtotal */
            $subtotal = $subtotals->get(Subtotal::TYPE_SUBTOTAL);

            $order->setSubtotal($subtotal->getAmount());
        }

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param string $identifier
     * @return null|Order
     */
    protected function getOrder(EntityManager $manager, $identifier)
    {
        if (!array_key_exists($identifier, $this->orders)) {
            $this->orders[$identifier] = $manager->getRepository('OroB2BOrderBundle:Order')
                ->findOneBy(['identifier' => $identifier]);
        }

        return $this->orders[$identifier];
    }

    /**
     * @param EntityManager $manager
     * @param string $sku
     * @return null|Product
     */
    protected function getProduct(EntityManager $manager, $sku)
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository('OroB2BProductBundle:Product')->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return null|ProductUnit
     */
    protected function getProductUnit(EntityManager $manager, $code)
    {
        return $manager->getReference('OroB2BProductBundle:ProductUnit', $code);
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param float $quantity
     * @param string $currency
     * @param PriceList $priceList
     * @return Price
     */
    protected function getPrice(Product $product, ProductUnit $productUnit, $quantity, $currency, PriceList $priceList)
    {
        $productPriceCriteria = new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
        $identifier = $productPriceCriteria->getIdentifier();

        if (!isset($this->prices[$priceList->getId()][$identifier])) {
            $prices = $this->productPriceProvider->getMatchedPrices([$productPriceCriteria], $priceList);
            $this->prices[$priceList->getId()][$identifier] = $prices[$identifier];
        }

        $price = $this->prices[$priceList->getId()][$identifier];

        return $price ?: Price::create(mt_rand(10, 1000), $currency);
    }
}
