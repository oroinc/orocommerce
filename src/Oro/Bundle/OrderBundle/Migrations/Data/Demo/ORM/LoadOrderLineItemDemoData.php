<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads demo data for order line items.
 */
class LoadOrderLineItemDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ProductPriceProviderInterface */
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
        $this->productPriceProvider = $container->get('oro_pricing.provider.product_price');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrderDemoData::class,
            LoadProductPriceDemoData::class,
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroOrderBundle/Migrations/Data/Demo/ORM/data/order-line-items.csv');

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
                $priceList = $this->container->get('oro_pricing.model.price_list_tree_handler')
                    ->getPriceList($order->getCustomer(), $order->getWebsite());
                if ($priceList) {
                    $price = $this->getPrice(
                        $product,
                        $productUnit,
                        $quantity,
                        $order->getCurrency(),
                        $priceList,
                        $order
                    );
                }
            }

            $date = null;
            if (!empty($row['shipBy'])) {
                $date = new \DateTime($row['shipBy']);
            }

            $priceTypes = [OrderLineItem::PRICE_TYPE_UNIT, OrderLineItem::PRICE_TYPE_BUNDLED];

            $orderLineItem
                ->setFromExternalSource(mt_rand(0, 1))
                ->setProduct($product)
                ->setProductName($product->getName())
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

        $totalHandler = $this->container->get('oro_order.order.total.total_helper');
        foreach ($this->orders as $order) {
            $totalHandler->fill($order);
        }

        $manager->flush();
    }

    protected function getOrder(EntityManagerInterface $manager, string $identifier): ?Order
    {
        if (!array_key_exists($identifier, $this->orders)) {
            $this->orders[$identifier] = $manager->getRepository('OroOrderBundle:Order')
                ->findOneBy(['identifier' => $identifier]);
        }

        return $this->orders[$identifier];
    }

    protected function getProduct(EntityManagerInterface $manager, string $sku): ?Product
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository('OroProductBundle:Product')->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    protected function getProductUnit(EntityManagerInterface $manager, string $code): ?ProductUnit
    {
        return $manager->getReference('OroProductBundle:ProductUnit', $code);
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param float $quantity
     * @param string $currency
     * @param BasePriceList $priceList
     * @param Order $order
     * @return Price
     */
    protected function getPrice(
        Product $product,
        ProductUnit $productUnit,
        $quantity,
        $currency,
        BasePriceList $priceList,
        Order $order
    ) {
        $productPriceCriteria = new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
        $identifier = $productPriceCriteria->getIdentifier();

        $priceListId = $priceList->getId();
        if (!isset($this->prices[$priceListId][$identifier])) {
            $searchScope = $this->getSearchScope($order);
            $prices = $this->productPriceProvider->getMatchedPrices([$productPriceCriteria], $searchScope);
            $this->prices[$priceListId][$identifier] = $prices[$identifier];
        }

        $price = $this->prices[$priceListId][$identifier];

        return $price ?: Price::create(mt_rand(10, 1000), $currency);
    }

    protected function getSearchScope(Order $order): ProductPriceScopeCriteriaInterface
    {
        return $this->container->get('oro_pricing.model.product_price_scope_criteria_factory')
            ->createByContext($order);
    }
}
